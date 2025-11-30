export interface NotificationOptions {
  message: string
  type?: 'success' | 'error' | 'info' | 'warning'
  title?: string
}

class NotificationService {
  private listeners: Array<(options: NotificationOptions) => void> = []

  subscribe(listener: (options: NotificationOptions) => void) {
    this.listeners.push(listener)
    return () => {
      this.listeners = this.listeners.filter(l => l !== listener)
    }
  }

  show(options: NotificationOptions) {
    const notificationOptions = {
      type: options.type || 'info',
      title: options.title,
      message: options.message
    }
    this.listeners.forEach(listener => listener(notificationOptions))
  }

  success(message: string, title?: string) {
    this.show({ message, type: 'success', title })
  }

  error(message: string, title?: string) {
    this.show({ message, type: 'error', title })
  }

  info(message: string, title?: string) {
    this.show({ message, type: 'info', title })
  }

  warning(message: string, title?: string) {
    this.show({ message, type: 'warning', title })
  }
}

export const notificationService = new NotificationService()

// Global notification function for backward compatibility
if (typeof window !== 'undefined') {
  ;(window as any).showNotification = (message: string, type: string = 'info') => {
    notificationService.show({ message, type: type as any })
  }
}

export default notificationService