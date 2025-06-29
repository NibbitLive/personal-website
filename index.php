<?php
session_start();
require_once 'db_connection.php';

$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $conn->prepare("SELECT COUNT(*) FROM banned_ips WHERE ip_address = ?");
$stmt->bind_param('s', $ip);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
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
		.new-messages-ping {
			position: fixed;
			top: 20px;
			left: 50%;
			transform: translateX(-50%);
			background-color:rgb(79, 175, 254);
			color: white;
			padding: 8px 16px;
			border-radius: 10px;
			box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
			font-weight: bold;
			z-index: 1000;
			opacity: 1;
			transition: opacity 1s ease;
			width: auto;
			max-width: 90%;
			max-height: 60px;
			display: inline-block;
			text-align: center;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		#vanta-bg {
			position: fixed;
			top: 0; left: 0; width: 100%; height: 100%;
			z-index: -1;
		}
		.message a {
			display: block;
			margin-top: 4px;
			font-size: 0.9rem;
		}
		.message img {
			max-width: 250px;
			max-height: 250px;
			margin-top: 5px;
			border-radius: 5px;
		}
		#filePreview {
			margin-top: 8px;
			font-size: 0.9rem;
			color: #333;
			display: flex;
			align-items: center;
			gap: 10px;
		}
		#filePreview button {
			font-size: 0.75rem;
			padding: 2px 6px;
		}
		#fileInput {
			display: none;
		}
		#selectFileBtn {
			margin-bottom: 10px;
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
				<a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
				<?php if ($username == 'Dihein'): ?>
					<a href="admin_user.php" class="btn btn-warning btn-sm mt-3">View Banned Users</a>
				<?php endif; ?>
			</div>

			<div class="chat-container">
				<div id="messages" class="mb-4" style="max-height: 400px; overflow-y: auto;"></div>
				<div class="input-container">
					<div class="form-group">
						<input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" readonly>
					</div>
					<div class="form-group">
						<textarea id="message-box" class="form-control" rows="3" placeholder="Type a message"></textarea>
					</div>
					<button type="button" id="selectFileBtn" class="btn btn-secondary btn-sm">Choose File</button>
					<input type="file" id="fileInput" />
					<div id="filePreview" style="display:none;">
						<span id="fileName"></span>
						<button type="button" id="removeFileBtn" class="btn btn-sm btn-outline-danger">Remove</button>
					</div>
					<button id="send-btn" class="btn btn-primary mt-2">Send</button>
					<div id="upload-error" style="color: red; margin-top: 5px;"></div>
				</div>
			</div>
		</div>
	</main>

	<script>
		const loggedInUsername = "<?php echo $username; ?>";
		let isFirstLoad = true;
		let lastLoadedMessageId = null;

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

		function loadMessages() {
			const editingMessageId = localStorage.getItem('editingMessageId');
			const messagesDiv = document.getElementById('messages');
			if (!messagesDiv) return;

			const scrollPosition = messagesDiv.scrollTop;
			const isAtBottom = messagesDiv.scrollHeight - messagesDiv.clientHeight === scrollPosition;

			fetch('chat.php')
				.then(response => response.json())
				.then(data => {
					data.reverse();
					const newMessages = data.filter(msg => !lastLoadedMessageId || msg.id > lastLoadedMessageId);

					// ✅ Only show ping if the most recent new message is not by the current user
					if (newMessages.length > 0 && !isFirstLoad) {
						const lastMsg = newMessages[newMessages.length - 1];
						if (lastMsg.username !== loggedInUsername) {
							showNewMessagesPing();
						}
					}

					if (data.length > 0) {
						const maxId = Math.max(...data.map(msg => msg.id));
						if (!lastLoadedMessageId || maxId > lastLoadedMessageId) {
							lastLoadedMessageId = maxId;
						}
					}

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

							if (message.file_path) {
								const ext = message.file_path.split('.').pop().toLowerCase();
								if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(ext)) {
									innerHTML += `<br><img src="${message.file_path}" alt="Image" />`;
								} else {
									innerHTML += `<br><a href="${message.file_path}" target="_blank">📎 View File</a>`;
								}
							}

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
				});
		}

		function showNewMessagesPing() {
			const existingPing = document.querySelector('.new-messages-ping');
			if (existingPing) existingPing.remove();

			const pingMessage = document.createElement('div');
			pingMessage.classList.add('new-messages-ping');
			pingMessage.textContent = "New messages are available!";
			document.body.appendChild(pingMessage);

			setTimeout(() => {
				pingMessage.style.opacity = '0';
			}, 3000);

			setTimeout(() => {
				pingMessage.remove();
			}, 4000);
		}

		function formatTimestamp(timestamp) {
			const date = new Date(timestamp.replace(' ', 'T'));
			if (isNaN(date.getTime())) return "Invalid Date";
			const hours = date.getHours().toString().padStart(2, '0');
			const minutes = date.getMinutes().toString().padStart(2, '0');
			return `${hours}:${minutes}`;
		}

		async function sendMessage() {
			const username = document.getElementById('username').value.trim();
			const messageBox = document.getElementById('message-box');
			const message = messageBox.value.trim();
			const fileInput = document.getElementById('fileInput');
			const uploadErrorDiv = document.getElementById('upload-error');
			uploadErrorDiv.textContent = '';

			if (!username) return alert('Username missing.');
			if (!message && fileInput.files.length === 0) return alert('Please enter a message or select a file.');

			const file = fileInput.files[0];
			const maxFileSize = 52428800;

			if (file && loggedInUsername !== 'Dihein' && file.size > maxFileSize) {
				uploadErrorDiv.textContent = 'File size exceeds the 50 MB limit.';
				return;
			}

			const formData = new FormData();
			formData.append('username', username);
			formData.append('message', message);
			if (file) formData.append('file', file);

			const response = await fetch('chat.php', {
				method: 'POST',
				body: formData
			});
			const result = await response.json();

			if (result.status === 'success') {
				messageBox.value = '';
				fileInput.value = '';
				clearFilePreview();
				loadMessages();
			} else {
				uploadErrorDiv.textContent = result.message || 'Unknown error occurred.';
			}
		}

		function editMessage(id, originalMessage) {
			localStorage.setItem('editingMessageId', id);
			const messageDiv = document.querySelector(`div[data-id="${id}"]`);
			if (!messageDiv) return;

			messageDiv.innerHTML = "";
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
						loadMessages();
					});
				} else {
					localStorage.removeItem('editingMessageId');
					loadMessages();
				}
			};

			const cancelBtn = document.createElement('button');
			cancelBtn.textContent = 'Cancel';
			cancelBtn.className = 'btn btn-secondary btn-sm';
			cancelBtn.onclick = () => {
				localStorage.removeItem('editingMessageId');
				loadMessages();
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

		// File preview logic
		const fileInput = document.getElementById('fileInput');
		const filePreview = document.getElementById('filePreview');
		const fileNameSpan = document.getElementById('fileName');
		const removeFileBtn = document.getElementById('removeFileBtn');
		const selectFileBtn = document.getElementById('selectFileBtn');

		selectFileBtn.addEventListener('click', () => fileInput.click());
		fileInput.addEventListener('change', () => {
			if (fileInput.files.length > 0) {
				fileNameSpan.textContent = fileInput.files[0].name;
				filePreview.style.display = 'flex';
			} else clearFilePreview();
		});
		removeFileBtn.addEventListener('click', () => {
			clearFilePreview();
			fileInput.value = '';
		});
		function clearFilePreview() {
			fileNameSpan.textContent = '';
			filePreview.style.display = 'none';
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
