<!-- Jerry Chatbot Widget -->
<div id="jerry-chatbot-container" style="position: fixed; bottom: 30px; right: 30px; z-index: 10000; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    
    <!-- Floating Avatar Bubble -->
    <div id="jerry-chat-bubble" class="shadow-lg d-flex justify-content-center align-items-center" style="width: 65px; height: 65px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color) 0%, #FF7043 100%); cursor: pointer; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; border: 2px solid #fff;">
        <img src="images/jerry_avatar.jpg" alt="Jerry" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
        <!-- Online Indicator Pulse -->
        <span style="position: absolute; bottom: 2px; right: 2px; width: 14px; height: 14px; background-color: #4CAF50; border-radius: 50%; border: 2px solid #fff; display: inline-block;">
            <span style="position: absolute; width: 100%; height: 100%; background-color: #4CAF50; border-radius: 50%; opacity: 0.75; animation: jerry-ping 1.2s cubic-bezier(0, 0, 0.2, 1) infinite;"></span>
        </span>
    </div>

    <!-- Chat Drawer/Window -->
    <div id="jerry-chat-window" class="shadow-lg border" style="display: none; width: 350px; height: 500px; border-radius: 16px; background-color: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); overflow: hidden; flex-direction: column; position: absolute; bottom: 80px; right: 0; transition: all 0.3s ease; transform: translateY(20px); opacity: 0;">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center text-white px-3 py-3" style="background: linear-gradient(135deg, #FF5722 0%, #E64A19 100%); border-top-left-radius: 15px; border-top-right-radius: 15px;">
            <div class="d-flex align-items-center">
                <img src="images/jerry_avatar.jpg" alt="Jerry" class="rounded-circle border" style="width: 40px; height: 40px; object-fit: cover; margin-right: 10px;">
                <div>
                    <h6 class="fw-bold mb-0" style="font-size: 0.95rem; letter-spacing: 0.5px;">Jerry</h6>
                    <span class="text-white-50" style="font-size: 0.75rem;"><i class="fas fa-circle text-success me-1" style="font-size: 0.6rem;"></i>Active Food Assistant</span>
                </div>
            </div>
            <button id="jerry-close-btn" class="btn btn-sm text-white border-0 p-1" style="font-size: 1.2rem; opacity: 0.85; transition: opacity 0.2s;"><i class="fas fa-times-circle"></i></button>
        </div>

        <!-- Chat History Pane -->
        <div id="jerry-chat-history" class="p-3 flex-grow-1" style="overflow-y: auto; background-color: #fcfdfd; scroll-behavior: smooth;">
            <!-- Welcome message -->
            <div class="d-flex mb-3">
                <div class="me-2">
                    <img src="images/jerry_avatar.jpg" class="rounded-circle border" style="width: 30px; height: 30px; object-fit: cover;">
                </div>
                <div class="p-2.5 rounded shadow-sm text-dark" style="background-color: #f1f2f6; max-width: 80%; border-radius: 12px; border-top-left-radius: 2px; font-size: 0.85rem;">
                    Hi! I'm <strong>Jerry</strong> 🤖✨, your custom food assistant. Ask me to recommend popular food, check delivery pincode, or search menu items! How can I help customize your order today?
                </div>
            </div>
        </div>

        <!-- Quick Suggestions Panel -->
        <div id="jerry-quick-replies" class="px-3 py-2 border-top d-flex flex-wrap gap-1.5 bg-light" style="font-size: 0.8rem; max-height: 90px; overflow-y: auto;">
            <button class="btn btn-xs btn-outline-primary py-1 px-2.5 rounded-pill jerry-suggestion" data-text="Check delivery pincode">Check Delivery Pincode</button>
            <button class="btn btn-xs btn-outline-primary py-1 px-2.5 rounded-pill jerry-suggestion" data-text="Recommend popular food">Recommend Food</button>
            <button class="btn btn-xs btn-outline-primary py-1 px-2.5 rounded-pill jerry-suggestion" data-text="Search Pizza">Search Pizza</button>
            <button class="btn btn-xs btn-outline-primary py-1 px-2.5 rounded-pill jerry-suggestion" data-text="Search Burgers">Search Burgers</button>
        </div>

        <!-- Footer Input Bar -->
        <form id="jerry-chat-form" class="p-2 bg-white border-top d-flex align-items-center">
            <input type="text" id="jerry-chat-input" class="form-control form-control-sm border-0 me-2" placeholder="Type a message or pincode..." style="box-shadow: none; font-size: 0.85rem; height: 38px;" autocomplete="off">
            <button type="submit" class="btn btn-primary btn-sm d-flex justify-content-center align-items-center rounded-circle" style="width: 36px; height: 36px; padding: 0; background-color: var(--primary-color) !important; border: none;"><i class="fas fa-paper-plane" style="font-size: 0.9rem;"></i></button>
        </form>
    </div>
</div>

<style>
@keyframes jerry-ping {
    75%, 100% {
        transform: scale(2);
        opacity: 0;
    }
}
.jerry-suggestion {
    font-size: 0.75rem !important;
    border-color: #ffab91 !important;
    color: #e64a19 !important;
    background-color: #fff !important;
    transition: all 0.2s ease;
    border-width: 1px;
}
.jerry-suggestion:hover {
    background-color: #e64a19 !important;
    color: #fff !important;
}
#jerry-chat-bubble:hover {
    transform: scale(1.1);
    box-shadow: 0 8px 24px rgba(255, 87, 34, 0.4) !important;
}
#jerry-close-btn:hover {
    opacity: 1;
    transform: scale(1.1);
}
.bot-add-to-cart {
    box-shadow: 0 2px 4px rgba(255,193,7,0.3);
    transition: all 0.2s;
}
.bot-add-to-cart:hover {
    transform: scale(1.05);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bubble = document.getElementById('jerry-chat-bubble');
    const windowDiv = document.getElementById('jerry-chat-window');
    const closeBtn = document.getElementById('jerry-close-btn');
    const chatForm = document.getElementById('jerry-chat-form');
    const chatInput = document.getElementById('jerry-chat-input');
    const historyDiv = document.getElementById('jerry-chat-history');
    
    // Toggle Chat Window
    bubble.addEventListener('click', function() {
        if (windowDiv.style.display === 'none' || windowDiv.style.display === '') {
            windowDiv.style.display = 'flex';
            setTimeout(() => {
                windowDiv.style.transform = 'translateY(0)';
                windowDiv.style.opacity = '1';
            }, 10);
        } else {
            closeChat();
        }
    });

    closeBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        closeChat();
    });

    function closeChat() {
        windowDiv.style.transform = 'translateY(20px)';
        windowDiv.style.opacity = '0';
        setTimeout(() => {
            windowDiv.style.display = 'none';
        }, 300);
    }

    // Handle Quick replies
    document.querySelectorAll('.jerry-suggestion').forEach(btn => {
        btn.addEventListener('click', function() {
            const text = this.getAttribute('data-text');
            chatInput.value = text;
            chatForm.dispatchEvent(new Event('submit'));
        });
    });

    // Append Message to History Pane
    function appendMessage(sender, text, isHtml = false) {
        const messageWrapper = document.createElement('div');
        messageWrapper.className = 'd-flex mb-3 ' + (sender === 'user' ? 'justify-content-end' : '');
        
        let avatar = '';
        if (sender === 'bot') {
            avatar = `<div class="me-2"><img src="images/jerry_avatar.jpg" class="rounded-circle border" style="width: 30px; height: 30px; object-fit: cover;"></div>`;
        }

        const bubbleStyle = sender === 'user' 
            ? 'background: linear-gradient(135deg, var(--primary-color) 0%, #FF7043 100%); color: white; border-radius: 12px; border-top-right-radius: 2px;'
            : 'background-color: #f1f2f6; color: #333; border-radius: 12px; border-top-left-radius: 2px;';

        const content = isHtml ? text : escapeHtml(text);

        messageWrapper.innerHTML = `
            ${avatar}
            <div class="p-2.5 rounded shadow-sm" style="${bubbleStyle} max-width: 80%; font-size: 0.85rem; word-break: break-word;">
                ${content}
            </div>
        `;
        
        historyDiv.appendChild(messageWrapper);
        historyDiv.scrollTop = historyDiv.scrollHeight;
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Submit Chat Message
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const message = chatInput.value.trim();
        if (message === '') return;
        
        // Append User message
        appendMessage('user', message);
        chatInput.value = '';

        // Typing indicator
        const typingId = 'jerry-typing';
        const typingWrapper = document.createElement('div');
        typingWrapper.id = typingId;
        typingWrapper.className = 'd-flex mb-3';
        typingWrapper.innerHTML = `
            <div class="me-2"><img src="images/jerry_avatar.jpg" class="rounded-circle border" style="width: 30px; height: 30px; object-fit: cover;"></div>
            <div class="p-2.5 rounded shadow-sm text-muted" style="background-color: #f1f2f6; border-radius: 12px; border-top-left-radius: 2px; font-size: 0.85rem;">
                <span class="spinner-grow spinner-grow-sm text-primary" role="status" aria-hidden="true" style="animation-duration: 0.75s;"></span>
                <span class="spinner-grow spinner-grow-sm text-primary ms-1" role="status" aria-hidden="true" style="animation-duration: 0.75s; animation-delay: 0.15s;"></span>
                <span class="spinner-grow spinner-grow-sm text-primary ms-1" role="status" aria-hidden="true" style="animation-duration: 0.75s; animation-delay: 0.3s;"></span>
            </div>
        `;
        historyDiv.appendChild(typingWrapper);
        historyDiv.scrollTop = historyDiv.scrollHeight;

        // Send to backend
        const formData = new FormData();
        formData.append('message', message);

        fetch('api/chatbot_query.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Remove typing indicator
            const typing = document.getElementById(typingId);
            if (typing) typing.remove();

            // Append bot reply
            appendMessage('bot', data.reply, true);

            // Update Quick Suggestions if returned
            const suggestionsDiv = document.getElementById('jerry-quick-replies');
            if (suggestionsDiv && data.options && data.options.length > 0) {
                suggestionsDiv.innerHTML = '';
                data.options.forEach(opt => {
                    const btn = document.createElement('button');
                    btn.className = 'btn btn-xs btn-outline-primary py-1 px-2.5 rounded-pill jerry-suggestion';
                    btn.setAttribute('data-text', opt);
                    btn.textContent = opt;
                    btn.addEventListener('click', function() {
                        chatInput.value = opt;
                        chatForm.dispatchEvent(new Event('submit'));
                    });
                    suggestionsDiv.appendChild(btn);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const typing = document.getElementById(typingId);
            if (typing) typing.remove();
            appendMessage('bot', 'Jerry is offline right now due to a network glitch. 🔌🤖');
        });
    });
});
</script>
