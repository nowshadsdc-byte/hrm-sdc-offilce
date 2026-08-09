<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentController extends Controller
{
    protected const DISK = 'local';

    protected const ALLOWED_MIMES = 'pdf,jpg,jpeg,png,doc,docx';

    protected const MAX_KILOBYTES = 10240;

    public function store(Request $request, Employee $employee)
    {
        $this->authorizeForEmployee($request, $employee);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:'.self::ALLOWED_MIMES, 'max:'.self::MAX_KILOBYTES],
        ]);

        $file = $validated['file'];
        $path = $file->store("employee-documents/{$employee->id}", self::DISK);

        $employee->documents()->create([
            'uploaded_by' => $request->user()->id,
            'title' => $validated['title'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function update(Request $request, Employee $employee, EmployeeDocument $document)
    {
        $this->authorizeForDocument($request, $employee, $document);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $document->update($validated);

        return back()->with('success', 'Document updated successfully.');
    }

    public function destroy(Request $request, Employee $employee, EmployeeDocument $document)
    {
        $this->authorizeForDocument($request, $employee, $document);

        Storage::disk(self::DISK)->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    public function preview(Request $request, Employee $employee, EmployeeDocument $document)
    {
        $this->authorizeForDocument($request, $employee, $document);

        return Storage::disk(self::DISK)->response($document->file_path, $document->original_filename, [
            'Content-Type' => $document->mime_type,
        ], 'inline');
    }

    public function download(Request $request, Employee $employee, EmployeeDocument $document)
    {
        $this->authorizeForDocument($request, $employee, $document);

        return Storage::disk(self::DISK)->download($document->file_path, $document->original_filename);
    }

    /**
     * Admins may manage documents for any employee; everyone else may only
     * manage documents on their own linked employee record.
     */
    protected function authorizeForEmployee(Request $request, Employee $employee): void
    {
        if ($this->userIsAdmin($request)) {
            return;
        }

        $user = $request->user();

        abort_unless($user && $user->employee && $user->employee->id === $employee->id, 403);
    }

    protected function authorizeForDocument(Request $request, Employee $employee, EmployeeDocument $document): void
    {
        abort_unless($document->employee_id === $employee->id, 404);

        $this->authorizeForEmployee($request, $employee);
    }

    protected function userIsAdmin(Request $request): bool
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'hasAnyRole')) {
            return false;
        }

        return $user->hasAnyRole(config('tyro-dashboard.admin_roles', ['admin', 'super-admin']));
    }
}
