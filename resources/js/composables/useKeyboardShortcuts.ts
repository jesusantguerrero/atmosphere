import { onMounted, onUnmounted } from 'vue';

export interface KeyboardShortcutHandlers {
  onNewTask?: () => void;
  onFocusSearch?: () => void;
  onToggleView?: () => void;
  onDelete?: () => void;
  onUndo?: () => void;
  onRedo?: () => void;
  onCloseModal?: () => void;
  onHelp?: () => void;
}

export function useKeyboardShortcuts(handlers: KeyboardShortcutHandlers) {
  // Detect platform for correct modifier key
  const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
  const modKey = isMac ? 'metaKey' : 'ctrlKey';

  function handleKeydown(e: KeyboardEvent) {
    // Ignore if user is typing in an input/textarea
    const target = e.target as HTMLElement;
    if (
      target.tagName === 'INPUT' ||
      target.tagName === 'TEXTAREA' ||
      target.isContentEditable
    ) {
      // Allow Esc even in inputs to close modals
      if (e.key === 'Escape' && handlers.onCloseModal) {
        e.preventDefault();
        handlers.onCloseModal();
      }
      return;
    }

    // Ctrl/Cmd + N: New task
    if (e[modKey] && e.key === 'n') {
      e.preventDefault();
      handlers.onNewTask?.();
    }

    // Ctrl/Cmd + K: Focus search
    if (e[modKey] && e.key === 'k') {
      e.preventDefault();
      handlers.onFocusSearch?.();
    }

    // Ctrl/Cmd + V: Toggle view
    if (e[modKey] && e.key === 'v') {
      e.preventDefault();
      handlers.onToggleView?.();
    }

    // Delete: Delete selected items
    if (e.key === 'Delete' && handlers.onDelete) {
      e.preventDefault();
      handlers.onDelete();
    }

    // Ctrl/Cmd + Z: Undo
    if (e[modKey] && !e.shiftKey && e.key === 'z') {
      e.preventDefault();
      handlers.onUndo?.();
    }

    // Ctrl/Cmd + Shift + Z: Redo
    if (e[modKey] && e.shiftKey && e.key === 'z') {
      e.preventDefault();
      handlers.onRedo?.();
    }

    // Esc: Close modal
    if (e.key === 'Escape' && handlers.onCloseModal) {
      e.preventDefault();
      handlers.onCloseModal();
    }

    // ?: Show help
    if (e.key === '?' && handlers.onHelp) {
      e.preventDefault();
      handlers.onHelp();
    }
  }

  onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
  });

  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
  });

  return {
    isMac,
    modKey: isMac ? '⌘' : 'Ctrl',
  };
}
