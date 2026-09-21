<?php

namespace App;

enum OpenWAWebhookEvent: string
{
    case All = '*';
    case MessageReceived = 'message.received';
    case MessageSent = 'message.sent';
    case MessageAck = 'message.ack';
    case MessageFailed = 'message.failed';
    case MessageRevoked = 'message.revoked';
    case MessageReaction = 'message.reaction';
    case MessageEdited = 'message.edited';
    case SessionStatus = 'session.status';
    case SessionQr = 'session.qr';
    case SessionAuthenticated = 'session.authenticated';
    case SessionDisconnected = 'session.disconnected';
    case SessionReconnectLoop = 'session.reconnect_loop';
    case SessionRestriction = 'session.restriction';
    case PresenceUpdate = 'presence.update';
    case GroupJoin = 'group.join';
    case GroupLeave = 'group.leave';
    case GroupUpdate = 'group.update';
    case GroupJoinRequest = 'group.join_request';
    case CallReceived = 'call.received';
    case CallAccepted = 'call.accepted';
    case CallRejected = 'call.rejected';
    case CallMissed = 'call.missed';
    case StatusReceived = 'status.received';
}
