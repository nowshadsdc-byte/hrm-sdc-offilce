<?php

use App\Http\Controllers\EmployeeDocumentController;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

function createEmployeeDocumentSchema(): void
{
    Schema::create('employees', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable();
        $table->string('name')->nullable();
        $table->timestamps();
    });

    Schema::create('employee_documents', function (Blueprint $table) {
        $table->id();
        $table->foreignId('employee_id');
        $table->foreignId('uploaded_by');
        $table->string('title');
        $table->string('file_path');
        $table->string('original_filename');
        $table->string('mime_type');
        $table->unsignedBigInteger('file_size');
        $table->timestamps();
    });
}

function makeAdminActor(): object
{
    return new class
    {
        public $id = 1;

        public function hasAnyRole(array $roles): bool
        {
            return true;
        }
    };
}

function makeEmployeeActor(int $id, ?Employee $employee): object
{
    return new class($id, $employee)
    {
        public $id;

        public $employee;

        public function __construct($id, $employee)
        {
            $this->id = $id;
            $this->employee = $employee;
        }

        public function hasAnyRole(array $roles): bool
        {
            return false;
        }
    };
}

it('allows an admin to upload a document for any employee', function () {
    createEmployeeDocumentSchema();
    Storage::fake('local');

    $employee = Employee::create(['name' => 'Target Employee']);
    $admin = makeAdminActor();

    $request = Request::create('/employees/'.$employee->id.'/documents', 'POST', [
        'title' => 'Employment Contract',
    ], [], [
        'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
    ]);
    $request->setUserResolver(fn () => $admin);

    $controller = app(EmployeeDocumentController::class);
    $response = $controller->store($request, $employee);

    expect($response->getSession()->get('success'))->toContain('uploaded');

    $document = EmployeeDocument::latest()->first();
    expect($document)->not()->toBeNull();
    expect($document->employee_id)->toBe($employee->id);
    expect($document->title)->toBe('Employment Contract');
    Storage::disk('local')->assertExists($document->file_path);
});

it('allows an employee to upload a document to their own record only', function () {
    createEmployeeDocumentSchema();
    Storage::fake('local');

    $ownEmployee = Employee::create(['name' => 'Own Record']);
    $otherEmployee = Employee::create(['name' => 'Someone Else']);
    $actor = makeEmployeeActor(5, $ownEmployee);

    $ownRequest = Request::create('/employees/'.$ownEmployee->id.'/documents', 'POST', [
        'title' => 'My NID',
    ], [], [
        'file' => UploadedFile::fake()->create('nid.pdf', 50, 'application/pdf'),
    ]);
    $ownRequest->setUserResolver(fn () => $actor);

    $controller = app(EmployeeDocumentController::class);
    $controller->store($ownRequest, $ownEmployee);

    expect(EmployeeDocument::where('employee_id', $ownEmployee->id)->count())->toBe(1);

    $otherRequest = Request::create('/employees/'.$otherEmployee->id.'/documents', 'POST', [
        'title' => 'Not Mine',
    ], [], [
        'file' => UploadedFile::fake()->create('other.pdf', 50, 'application/pdf'),
    ]);
    $otherRequest->setUserResolver(fn () => $actor);

    expect(fn () => $controller->store($otherRequest, $otherEmployee))
        ->toThrow(HttpException::class);

    expect(EmployeeDocument::where('employee_id', $otherEmployee->id)->count())->toBe(0);
});

it('rejects file uploads with a disallowed mime type', function () {
    createEmployeeDocumentSchema();
    Storage::fake('local');

    $employee = Employee::create(['name' => 'Target Employee']);
    $admin = makeAdminActor();

    $request = Request::create('/employees/'.$employee->id.'/documents', 'POST', [
        'title' => 'Suspicious File',
    ], [], [
        'file' => UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload'),
    ]);
    $request->setUserResolver(fn () => $admin);

    $controller = app(EmployeeDocumentController::class);

    expect(fn () => $controller->store($request, $employee))
        ->toThrow(ValidationException::class);
});

it('lets an employee update and delete only their own document, not another employee\'s', function () {
    createEmployeeDocumentSchema();
    Storage::fake('local');

    $ownEmployee = Employee::create(['name' => 'Own Record']);
    $otherEmployee = Employee::create(['name' => 'Someone Else']);

    $ownDocument = EmployeeDocument::create([
        'employee_id' => $ownEmployee->id,
        'uploaded_by' => 1,
        'title' => 'Original Title',
        'file_path' => 'employee-documents/'.$ownEmployee->id.'/file.pdf',
        'original_filename' => 'file.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 100,
    ]);

    $otherDocument = EmployeeDocument::create([
        'employee_id' => $otherEmployee->id,
        'uploaded_by' => 1,
        'title' => 'Other Title',
        'file_path' => 'employee-documents/'.$otherEmployee->id.'/file.pdf',
        'original_filename' => 'file.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 100,
    ]);

    $actor = makeEmployeeActor(5, $ownEmployee);
    $controller = app(EmployeeDocumentController::class);

    $updateRequest = Request::create('/employees/'.$ownEmployee->id.'/documents/'.$ownDocument->id, 'PUT', [
        'title' => 'Updated Title',
    ]);
    $updateRequest->setUserResolver(fn () => $actor);

    $controller->update($updateRequest, $ownEmployee, $ownDocument);
    expect($ownDocument->fresh()->title)->toBe('Updated Title');

    $forbiddenUpdateRequest = Request::create('/employees/'.$otherEmployee->id.'/documents/'.$otherDocument->id, 'PUT', [
        'title' => 'Hijacked Title',
    ]);
    $forbiddenUpdateRequest->setUserResolver(fn () => $actor);

    expect(fn () => $controller->update($forbiddenUpdateRequest, $otherEmployee, $otherDocument))
        ->toThrow(HttpException::class);
    expect($otherDocument->fresh()->title)->toBe('Other Title');

    $forbiddenDeleteRequest = Request::create('/employees/'.$otherEmployee->id.'/documents/'.$otherDocument->id, 'DELETE');
    $forbiddenDeleteRequest->setUserResolver(fn () => $actor);

    expect(fn () => $controller->destroy($forbiddenDeleteRequest, $otherEmployee, $otherDocument))
        ->toThrow(HttpException::class);
    expect(EmployeeDocument::find($otherDocument->id))->not()->toBeNull();

    $deleteRequest = Request::create('/employees/'.$ownEmployee->id.'/documents/'.$ownDocument->id, 'DELETE');
    $deleteRequest->setUserResolver(fn () => $actor);

    $controller->destroy($deleteRequest, $ownEmployee, $ownDocument);
    expect(EmployeeDocument::find($ownDocument->id))->toBeNull();
});
