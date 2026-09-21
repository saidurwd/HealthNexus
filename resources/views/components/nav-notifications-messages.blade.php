@php
    $notificationsCount = 0;
    $messagesCount = 0;
@endphp

{{-- Notifications Dropdown --}}
<li class="nav-item dropdown">
    <a class="nav-link" data-bs-toggle="dropdown" href="#" role="button"
       data-bs-auto-close="outside" data-display="static"
       aria-expanded="false">
        <i class="bi bi-bell"></i>
        @if ($notificationsCount > 0)
            <span class="navbar-badge badge text-bg-danger">{{ $notificationsCount }}</span>
        @endif
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow"
         style="width: 320px; max-width: 100%;">
        <span class="dropdown-header bg-primary">
            <i class="bi bi-bell-fill me-2"></i> {{ $notificationsCount }} Notification(s)
        </span>
        <div class="dropdown-divider"></div>
        <div class="text-center mt-2 pb-2">
            <i class="bi bi-info-circle display-4 text-muted"></i>
            <p class="text-muted mt-2 mb-0">No new notifications</p>
        </div>
        <div class="dropdown-divider"></div>
        <a href="{{ route('admin.notifications.index') }}"
           class="dropdown-item dropdown-footer text-center">
            <i class="bi bi-arrow-right-circle"></i> See All
        </a>
    </div>
</li>

{{-- Messages Dropdown --}}
<li class="nav-item dropdown">
    <a class="nav-link" data-bs-toggle="dropdown" href="#" role="button"
       data-bs-auto-close="outside" data-display="static"
       aria-expanded="false">
        <i class="bi bi-chat-left-text"></i>
        @if ($messagesCount > 0)
            <span class="navbar-badge badge text-bg-danger">{{ $messagesCount }}</span>
        @endif
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow"
         style="width: 320px; max-width: 100%;">
        <span class="dropdown-header bg-primary">
            <i class="bi bi-chat-left-text-fill me-2"></i> {{ $messagesCount }} Message(s)
        </span>
        <div class="dropdown-divider"></div>
        <div class="text-center mt-2 pb-2">
            <i class="bi bi-chat-left display-4 text-muted"></i>
            <p class="text-muted mt-2 mb-0">No new messages</p>
        </div>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item dropdown-footer text-center">
            <i class="bi bi-arrow-right-circle"></i> See All
        </a>
    </div>
</li>
