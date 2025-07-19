@extends('layouts.app')

@section('sidebar')
    @include('layouts.sidebar')
@endsection

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-message-circle me-2"></i>
                        AI Chat Assistant - Customer
                    </h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" id="clearChat">
                            <i class="ti ti-trash me-1"></i>
                            Clear Chat
                        </button>
                        <button class="btn btn-outline-primary btn-sm" id="newChat">
                            <i class="ti ti-plus me-1"></i>
                            New Chat
                        </button>
                    </div>
                </div>
                
                <div class="card-body p-0 d-flex flex-column" style="height: calc(100vh - 200px);">
                    <!-- Chat Messages Area -->
                    <div class="flex-grow-1 overflow-auto p-4" id="chatMessages" style="max-height: calc(100vh - 300px);">
                        <!-- Welcome Message -->
                        <div class="d-flex justify-content-start mb-4">
                            <div class="d-flex align-items-start gap-3">
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-sm">
                                        <div class="avatar-initial rounded-circle bg-primary">
                                            <i class="ti ti-robot text-white"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="bg-light rounded-3 p-3" style="max-width: 80%;">
                                        <p class="mb-0 text-dark">
                                            Hello! I'm your AI assistant. How can I help you today? You can ask me about our services, check your account information, or get help with your orders.
                                        </p>
                                    </div>
                                    <small class="text-muted mt-1 d-block">Just now</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Input Area -->
                    <div class="border-top p-4">
                        <div class="d-flex gap-3 align-items-end">
                            <div class="flex-grow-1">
                                <div class="form-floating">
                                    <textarea 
                                        class="form-control" 
                                        id="messageInput" 
                                        placeholder="Type your message here..." 
                                        style="height: 60px; resize: none;"
                                        rows="1"></textarea>
                                    <label for="messageInput">Type your message here...</label>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <button class="btn btn-primary" id="sendMessage" disabled>
                                    <i class="ti ti-send me-1"></i>
                                    Send
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="ti ti-info-circle me-1"></i>
                                Press Enter to send, Shift+Enter for new line
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
/* Custom styles for ChatGPT-like interface */
#chatMessages::-webkit-scrollbar {
    width: 6px;
}

#chatMessages::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#chatMessages::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#chatMessages::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.message-user {
    justify-content: flex-end;
}

.message-user .bg-light {
    background-color: #007bff !important;
    color: white !important;
}

.message-ai .bg-light {
    background-color: #f8f9fa !important;
    border: 1px solid #e9ecef;
}

.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 8px 12px;
}

.typing-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #6c757d;
    animation: typing 1.4s infinite ease-in-out;
}

.typing-dot:nth-child(1) { animation-delay: -0.32s; }
.typing-dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing {
    0%, 80%, 100% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}

.avatar-initial {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageInput = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendMessage');
    const chatMessages = document.getElementById('chatMessages');
    const clearChatBtn = document.getElementById('clearChat');
    const newChatBtn = document.getElementById('newChat');
    
    let isTyping = false;
    
    // Auto-resize textarea
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        sendButton.disabled = this.value.trim().length === 0;
    });
    
    // Handle Enter key
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (!sendButton.disabled && !isTyping) {
                sendMessage();
            }
        }
    });
    
    // Send message function
    function sendMessage() {
        const message = messageInput.value.trim();
        if (!message || isTyping) return;
        
        // Add user message
        addMessage(message, 'user');
        
        // Clear input
        messageInput.value = '';
        messageInput.style.height = 'auto';
        sendButton.disabled = true;
        
        // Show typing indicator
        showTypingIndicator();
        
        // Kirim ke backend Laravel
        fetch("{{ route('chat.send') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ message })
        })
        .then(res => res.json())
        .then(data => {
            hideTypingIndicator();
            if (data.success) {
                addMessage(data.message, 'ai', data.timestamp);
            } else {
                addMessage('Maaf, terjadi kesalahan: ' + (data.message || 'Tidak diketahui'), 'ai');
            }
        })
        .catch(err => {
            hideTypingIndicator();
            addMessage('Maaf, terjadi kesalahan koneksi.', 'ai');
        });
    }

    // Add message to chat
    function addMessage(text, sender, timestamp = null) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `d-flex justify-content-${sender === 'user' ? 'end' : 'start'} mb-4 message-${sender}`;
        if (!timestamp) {
            timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        messageDiv.innerHTML = `
            <div class="d-flex align-items-start gap-3">
                ${sender === 'ai' ? `
                    <div class="flex-shrink-0">
                        <div class="avatar avatar-sm">
                            <div class="avatar-initial rounded-circle bg-primary">
                                <i class="ti ti-robot text-white"></i>
                            </div>
                        </div>
                    </div>
                ` : ''}
                <div class="flex-grow-1">
                    <div class="bg-light rounded-3 p-3" style="max-width: 80%;">
                        <p class="mb-0 text-dark">${text}</p>
                    </div>
                    <small class="text-muted mt-1 d-block">${timestamp}</small>
                </div>
                ${sender === 'user' ? `
                    <div class="flex-shrink-0">
                        <div class="avatar avatar-sm">
                            <div class="avatar-initial rounded-circle bg-secondary">
                                <i class="ti ti-user text-white"></i>
                            </div>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    }

    // Show typing indicator
    function showTypingIndicator() {
        isTyping = true;
        const typingDiv = document.createElement('div');
        typingDiv.className = 'd-flex justify-content-start mb-4 message-ai';
        typingDiv.id = 'typingIndicator';
        typingDiv.innerHTML = `
            <div class="d-flex align-items-start gap-3">
                <div class="flex-shrink-0">
                    <div class="avatar avatar-sm">
                        <div class="avatar-initial rounded-circle bg-primary">
                            <i class="ti ti-robot text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="bg-light rounded-3 p-3" style="max-width: 80%;">
                        <div class="typing-indicator">
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        chatMessages.appendChild(typingDiv);
        scrollToBottom();
    }

    // Hide typing indicator
    function hideTypingIndicator() {
        isTyping = false;
        const typingIndicator = document.getElementById('typingIndicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }

    // Scroll to bottom
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Event listeners
    sendButton.addEventListener('click', sendMessage);

    clearChatBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to clear the chat history?')) {
            chatMessages.innerHTML = `
                <div class="d-flex justify-content-start mb-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-sm">
                                <div class="avatar-initial rounded-circle bg-primary">
                                    <i class="ti ti-robot text-white"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="bg-light rounded-3 p-3" style="max-width: 80%;">
                                <p class="mb-0 text-dark">
                                    Hello! I'm your AI assistant. How can I help you today? You can ask me about our services, check your account information, or get help with your orders.
                                </p>
                            </div>
                            <small class="text-muted mt-1 d-block">Just now</small>
                        </div>
                    </div>
                </div>
            `;
            scrollToBottom();
        }
    });

    newChatBtn.addEventListener('click', function() {
        if (confirm('Apakah Anda yakin ingin memulai chat baru?')) {
            // Redirect to a new chat page or clear current chat
            window.location.href = "{{ route('chat.index') }}";
        }
    });
});
</script>
