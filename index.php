<?php
session_start();

require_once 'db_connection.php'; // Use your existing connection

// Check if current IP is banned
$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $pdo->prepare("SELECT COUNT(*) FROM banned_ips WHERE ip_address = ?");
$stmt->execute([$ip]);

if ($stmt->fetchColumn() > 0) {
	http_response_code(403);
	exit('Access denied: Your IP has been banned.');
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BineChat</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="styles.css" />
        <link rel="icon" href="images/BC_1.png" type="image/png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.birds.min.js"></script>
    <style>
        .edit-btn, .delete-btn {
            margin-left: 10px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div id="vanta-bg"></div>

    <header>
        <h1 class="font-weight-bold" style="font-size: 2.5rem; letter-spacing: 2px; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.4);">BineChat</h1>
    </header>

    <main>
        <div class="main-container">
            <div class="sidebar">
                <div class="friend-requests">
                    <a href="friend_requests.php" class="btn btn-link p-0" style="font-size: 1.5rem; color: #007bff;">Friends</a>
                </div>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>

                <?php if ($username == 'Dihein'): ?>
                    <!-- Show the button only if the username is 'Dihein' -->
                    <a href="admin_user.php" class="btn btn-warning btn-sm mt-3">View Banned Users</a>
                <?php endif; ?>

            </div>

            <div class="chat-container">
                <div id="new-message-notification" style="display: none; background-color: #007bff; color: white; padding: 10px; text-align: center; cursor: pointer;">
                    New message(s) arrived! Click to view.
                </div>
                <div id="messages" class="mb-4"></div>
                <div class="input-container">
                    <div class="form-group">
                        <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <textarea id="message-box" class="form-control" rows="3" placeholder="Type a message" required></textarea>
                    </div>
                    <button id="send-btn" class="btn btn-primary">Send</button>
                </div>
            </div>
        </div>

        <div class="send-friend-request">
            <h3>Send Friend Request</h3>
            <form action="send_friend_request.php" method="POST">
                <input type="text" name="friend_username" placeholder="Enter username" class="form-control" required />
                <button type="submit" class="btn btn-primary mt-2">Send Request</button>
            </form>
        </div>
    </main>

    <script>
        const loggedInUsername = "<?php echo $username; ?>";
        let isFirstLoad = true;
        let editingMessageId = localStorage.getItem('editingMessageId');  // Get the editing state from localStorage
        let lastMessageTimestamp = 0;  // Variable to track the timestamp of the last message
        let isScrolledUp = false;  // To check if the user is scrolling up

        VANTA.BIRDS({
            el: "#vanta-bg",
            mouseControls: true,
            touchControls: true,
            gyroControls: false,
            minHeight: 200.00,
            minWidth: 200.00,
            scale: 1.00,
            scaleMobile: 1.00,
            color1: 0x98c0ff,
            color2: 0x666666,
            birdSize: 0.30,
            wingSpan: 13.00,
            speedLimit: 2.00,
            separation: 49.00,
            alignment: 29.00,
            cohesion: 99.00
        });

        let lastLoadedMessageId = null;

        function loadMessages() {
            const editingMessageId = localStorage.getItem('editingMessageId');
            const messagesDiv = document.getElementById('messages');

            if (!messagesDiv) return;

            const scrollPosition = messagesDiv.scrollTop;
            const isAtBottom = messagesDiv.scrollHeight - messagesDiv.clientHeight === scrollPosition;

            fetch('chat.php')
                .then(response => {
                    if (!response.ok) throw new Error('Failed to fetch chat data');
                    return response.json();
                })
                .then(data => {
                    data.reverse();  // Ensure messages are in correct order

                    // Check if there are new messages
                    const newMessages = data.filter(message => message.id > lastLoadedMessageId);
                    if (newMessages.length > 0 && !isFirstLoad) {
                            const notification = document.getElementById('new-message-notification');
                            notification.style.display = 'block';
                            showNewMessagesPing();
                    }

                    // Update last loaded message ID
                    if (data.length > 0) {
                        // Set to the highest message ID
                        const maxId = Math.max(...data.map(msg => msg.id));
                        if (maxId > lastLoadedMessageId) {
                            lastLoadedMessageId = maxId;
                        }
                    }

                    // Only update the DOM if we're not editing a message
                    if (!editingMessageId) {
                        messagesDiv.innerHTML = "";

                        data.forEach(message => {
                            const messageDiv = document.createElement('div');
                            messageDiv.classList.add('message');
                            messageDiv.setAttribute('data-id', message.id);

                            const timestamp = formatTimestamp(message.created_at);
                            const editedText = message.edited_at ? ' <span class="edited">(edited)</span>' : '';

                            const msgContent = document.createElement('span');
                            msgContent.className = 'msg-content';
                            msgContent.textContent = message.message;

                            let innerHTML = `<strong>${message.username}:</strong> ${msgContent.outerHTML}${editedText} <span class="timestamp">(${timestamp})</span>`;

                            if (message.username === loggedInUsername) {
                                innerHTML += `
                                    <button class="btn btn-sm btn-outline-secondary edit-btn" onclick="editMessage(${message.id}, '${message.message.replace(/'/g, "\\'")}')">Edit</button>
                                    <button class="btn btn-sm btn-outline-danger delete-btn" onclick="deleteMessage(${message.id})">Delete</button>
                                `;
                            }

                            messageDiv.innerHTML = innerHTML;
                            messagesDiv.appendChild(messageDiv);
                        });

                        if (isFirstLoad || isAtBottom) {
                            messagesDiv.scrollTop = messagesDiv.scrollHeight;
                        }

                        isFirstLoad = false;
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                });
        }

        // Add the notification click handler to scroll to the bottom of the chat
        document.getElementById('new-message-notification').addEventListener('click', () => {
            const messagesDiv = document.getElementById('messages');
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            document.getElementById('new-message-notification').style.display = 'none'; // Hide the notification
        });
        function showNewMessagesPing() {
            const pingMessage = document.createElement('div');
            pingMessage.classList.add('new-messages-ping');
            pingMessage.textContent = "New messages are available!";
            document.body.appendChild(pingMessage);

            setTimeout(() => {
                pingMessage.remove();  // Remove the ping after a few seconds
            }, 5000);
        }

        function formatTimestamp(timestamp) {
            const date = new Date(timestamp.replace(' ', 'T'));
            if (isNaN(date.getTime())) return "Invalid Date";
            const hours = date.getHours().toString().padStart(2, '0');
            const minutes = date.getMinutes().toString().padStart(2, '0');
            return `${hours}:${minutes}`;
        }

        function sendMessage() {
            const username = document.getElementById('username').value.trim();
            const messageBox = document.getElementById('message-box');
            const message = messageBox.value.trim();

            if (username && message) {
                fetch('chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `username=${encodeURIComponent(username)}&message=${encodeURIComponent(message)}`
                }).then(() => {
                    messageBox.value = '';
                    loadMessages();
                });
            }
        }

        function editMessage(id, originalMessage) {
            localStorage.setItem('editingMessageId', id);

            const messageDiv = document.querySelector(`div[data-id="${id}"]`);
            if (!messageDiv) return;

            messageDiv.innerHTML = ""; // Clear message div

            const textarea = document.createElement('textarea');
            textarea.classList.add('form-control', 'my-2');
            textarea.value = originalMessage;

            const saveBtn = document.createElement('button');
            saveBtn.textContent = 'Save';
            saveBtn.className = 'btn btn-success btn-sm me-2';
            saveBtn.onclick = () => {
                const newMessage = textarea.value.trim();
                if (newMessage && newMessage !== originalMessage) {
                    fetch('chat.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `edit_id=${id}&new_message=${encodeURIComponent(newMessage)}`
                    }).then(() => {
                        localStorage.removeItem('editingMessageId');
                        loadMessages(); // Reload to show updated message
                    });
                } else {
                    localStorage.removeItem('editingMessageId');
                    loadMessages(); // Cancel edit if no change
                }
            };

            const cancelBtn = document.createElement('button');
            cancelBtn.textContent = 'Cancel';
            cancelBtn.className = 'btn btn-secondary btn-sm';
            cancelBtn.onclick = () => {
                localStorage.removeItem('editingMessageId');
                loadMessages(); // Cancel and reload
            };

            messageDiv.appendChild(textarea);
            messageDiv.appendChild(saveBtn);
            messageDiv.appendChild(cancelBtn);
        }

        function deleteMessage(id) {
            if (!confirm("Are you sure you want to delete this message?")) return;
            fetch('chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `delete_id=${id}`
            }).then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    loadMessages();
                } else {
                    alert(data.message || "Error deleting message");
                }
            });
        }

        document.getElementById('send-btn').addEventListener('click', e => {
            e.preventDefault();
            sendMessage();
        });

        document.getElementById('message-box').addEventListener('keypress', e => {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        window.onload = loadMessages;
        setInterval(loadMessages, 2000);
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
