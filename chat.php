 <?php
session_start();
// This page is protected; redirect to login if not a user or helper.
if (!isset($_SESSION['user_id']) && !isset($_SESSION['helper_id'])) {
    header('Location: login.php');
    exit;
}
// Pass current user's info to JavaScript in a secure way.
$currentUser = [
    'id' => $_SESSION['user_id'] ?? $_SESSION['helper_id'],
    'role' => isset($_SESSION['user_id']) ? 'user' : 'helper'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - TogetherA+</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css"> <!-- Your global stylesheet -->
    <style>
        :root {
            --primary-color: #0d6efd;
            --light-primary: #e7f3ff;
            --border-color: #dee2e6;
            --bg-light: #f8f9fa;
        }
        body { font-family: 'Nunito', sans-serif; background-color: var(--bg-light); margin: 0; }
        .chat-container {
            display: flex;
            height: calc(100vh - 70px); /* Adjust based on your header's height */
            max-width: 1100px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .conversation-list {
            width: 320px;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
        }
        .conversation-header { padding: 1rem; font-size: 20px; font-weight: 700; border-bottom: 1px solid var(--border-color); }
        .conversations { flex-grow: 1; overflow-y: auto; }
        .conversation { display: flex; align-items: center; padding: 1rem; cursor: pointer; transition: background-color 0.2s; border-bottom: 1px solid var(--border-color); }
        .conversation:hover { background-color: var(--bg-light); }
        .conversation.active { background-color: var(--light-primary); }
        .conversation img { width: 50px; height: 50px; border-radius: 50%; margin-right: 1rem; }
        .conversation-details { flex-grow: 1; overflow: hidden; }
        .conversation-details .name { font-weight: 600; }
        .conversation-details .last-message { font-size: 14px; color: #6c757d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .unread-badge { background-color: var(--primary-color); color: white; font-size: 12px; font-weight: bold; padding: 3px 8px; border-radius: 10px; }

        .chat-window { flex-grow: 1; display: flex; flex-direction: column; }
        .chat-header { padding: 1rem; border-bottom: 1px solid var(--border-color); font-weight: 700; font-size: 18px; }
        .chat-messages { flex-grow: 1; padding: 1rem; overflow-y: auto; display: flex; flex-direction: column; }
        .message-bubble { max-width: 70%; padding: 10px 15px; border-radius: 18px; margin-bottom: 10px; line-height: 1.5; }
        .message-bubble.sender { background-color: var(--primary-color); color: white; align-self: flex-end; border-bottom-right-radius: 5px; }
        .message-bubble.receiver { background-color: #e9ecef; color: #212529; align-self: flex-start; border-bottom-left-radius: 5px; }
        .chat-input-form { display: flex; padding: 1rem; border-top: 1px solid var(--border-color); }
        #message-input { flex-grow: 1; border: 1px solid var(--border-color); border-radius: 20px; padding: 10px 15px; font-size: 16px; }
        #message-input:focus { outline: none; border-color: var(--primary-color); }
        .send-btn { background: var(--primary-color); border: none; color: white; border-radius: 50%; width: 40px; height: 40px; margin-left: 10px; cursor: pointer; display: flex; justify-content: center; align-items: center; }
        .placeholder-chat { display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100%; color: #6c757d; }
    </style>
</head>
<body>
    <?php include ($currentUser['role'] === 'user' ? 'header_user.php' : 'header_helper.php'); ?>

    <div class="chat-container">
        <!-- Left Panel: List of all conversations -->
        <div class="conversation-list">
            <div class="conversation-header">Messages</div>
            <div class="conversations" id="conversation-list-items">
                <!-- Conversations will be loaded here by JavaScript -->
            </div>
        </div>
        <!-- Right Panel: The active chat window -->
        <div class="chat-window">
            <div id="chat-area">
                <div class="chat-header" id="chat-header-name">Select a conversation</div>
                <div class="chat-messages" id="chat-messages-area">
                    <div class="placeholder-chat">
                        <i class="fas fa-comments fa-3x"></i>
                        <p>Your messages will appear here.</p>
                    </div>
                </div>
                <form class="chat-input-form" id="chat-form" style="display: none;">
                    <input type="text" id="message-input" placeholder="Type a message..." autocomplete="off">
                    <button type="submit" class="send-btn"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const currentUser = <?php echo json_encode($currentUser); ?>;
        const conversationListEl = document.getElementById('conversation-list-items');
        const chatHeaderEl = document.getElementById('chat-header-name');
        const chatMessagesAreaEl = document.getElementById('chat-messages-area');
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');

        let activeContact = null;
        let messageInterval = null;

        // Fetches all conversations from the backend and displays them in the left panel.
        async function loadConversations() {
            const response = await fetch(`chat_handler.php?action=get_conversations`);
            const conversations = await response.json();
            
            conversationListEl.innerHTML = '';
            conversations.forEach(convo => {
                const convoEl = document.createElement('div');
                convoEl.className = 'conversation';
                convoEl.dataset.contactId = convo.contact_id;
                convoEl.dataset.contactRole = convo.contact_role;
                convoEl.dataset.contactName = convo.contact_name;
                convoEl.innerHTML = `
                    <img src="img/default-avatar.png" alt="">
                    <div class="conversation-details">
                        <div class="name">${convo.contact_name}</div>
                        <div class="last-message">${convo.message_content}</div>
                    </div>
                    ${convo.unread_count > 0 ? `<span class="unread-badge">${convo.unread_count}</span>` : ''}
                `;
                convoEl.addEventListener('click', () => selectConversation(convoEl));
                conversationListEl.appendChild(convoEl);
            });
        }

        // Handles what happens when a user clicks on a conversation.
        function selectConversation(element) {
            activeContact = {
                id: element.dataset.contactId,
                role: element.dataset.contactRole,
                name: element.dataset.contactName
            };
            
            document.querySelectorAll('.conversation').forEach(el => el.classList.remove('active'));
            element.classList.add('active');

            chatHeaderEl.textContent = `Chat with ${activeContact.name}`;
            chatForm.style.display = 'flex';
            loadMessages(); // Load messages for the selected chat

            if (messageInterval) clearInterval(messageInterval);
            messageInterval = setInterval(loadMessages, 5000); // Poll for new messages every 5 seconds.
        }

        // Fetches and displays the message history for the active chat.
        async function loadMessages() {
            if (!activeContact) return;
            const response = await fetch(`chat_handler.php?action=get_messages&contact_id=${activeContact.id}&contact_role=${activeContact.role}`);
            const messages = await response.json();
            
            chatMessagesAreaEl.innerHTML = ''; // Clear previous messages
            messages.forEach(msg => {
                const bubble = document.createElement('div');
                bubble.className = 'message-bubble';
                // Determine if the message was sent by the current user or the contact
                bubble.classList.add(msg.sender_id == currentUser.id && msg.sender_role == currentUser.role ? 'sender' : 'receiver');
                bubble.textContent = msg.message_content;
                chatMessagesAreaEl.appendChild(bubble);
            });
            scrollToBottom();
            // Refresh conversation list to update unread counts
            if (document.hasFocus()) loadConversations();
        }

        // Handles the submission of the message form.
        async function sendMessage(event) {
            event.preventDefault();
            const message = messageInput.value.trim();
            if (!message || !activeContact) return;

            // Immediately display the sent message for a better user experience
            const tempBubble = document.createElement('div');
            tempBubble.className = 'message-bubble sender';
            tempBubble.textContent = message;
            chatMessagesAreaEl.appendChild(tempBubble);
            scrollToBottom();
            messageInput.value = '';

            await fetch('chat_handler.php?action=send_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    receiver_id: activeContact.id,
                    receiver_role: activeContact.role,
                    message: message
                })
            });
            
            // Reload messages from server to confirm it was sent
            loadMessages();
        }
        
        // Utility to scroll the message area to the bottom.
        function scrollToBottom() {
            chatMessagesAreaEl.scrollTop = chatMessagesAreaEl.scrollHeight;
        }

        chatForm.addEventListener('submit', sendMessage);
        
        // --- Integration Logic ---
        // This part checks if the page was opened with a specific contact in mind.
        document.addEventListener('DOMContentLoaded', async () => {
            await loadConversations(); // Wait for conversations to load first

            const urlParams = new URLSearchParams(window.location.search);
            const contactId = urlParams.get('contact_id');
            const contactRole = urlParams.get('contact_role');

            if (contactId && contactRole) {
                const convoEl = document.querySelector(`.conversation[data-contact-id='${contactId}'][data-contact-role='${contactRole}']`);
                if (convoEl) {
                    convoEl.click(); // Simulate a click to open the conversation
                }
            }
        });
    </script>
</body>
</html>