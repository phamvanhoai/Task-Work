<div class="action-menu">
    <button class="action-menu-toggle" type="button" aria-label="Thao tác" aria-expanded="false"><i data-lucide="more-vertical"></i></button>
    <div class="action-menu-popover">
        <a href="{{ route('tasks.edit', $task) }}"><i data-lucide="pencil"></i>Sửa</a>
        <button type="button" data-delete-action="{{ route('tasks.destroy', $task) }}" data-delete-message="Xóa task này? Hành động không thể hoàn tác."><i data-lucide="trash-2"></i>Xóa</button>
    </div>
</div>
