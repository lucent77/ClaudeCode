// ==================== State Management ====================
const state = {
  user: null,
  token: null,
  socket: null,
  currentChannel: null,
  currentDM: null,
  channels: [],
  users: [],
  onlineUsers: new Set(),
  messages: [],
  unreadDMs: new Map(),
  typingTimeout: null,
  isTyping: false
};

// ==================== API Helpers ====================
const API_BASE = '/api';

async function api(endpoint, options = {}) {
  const headers = {
    'Content-Type': 'application/json',
    ...options.headers
  };

  if (state.token) {
    headers['Authorization'] = `Bearer ${state.token}`;
  }

  const response = await fetch(`${API_BASE}${endpoint}`, {
    ...options,
    headers
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.error || 'API request failed');
  }

  return data;
}

// ==================== Authentication ====================
function showLogin() {
  document.getElementById('login-form').classList.remove('hidden');
  document.getElementById('register-form').classList.add('hidden');
}

function showRegister() {
  document.getElementById('login-form').classList.add('hidden');
  document.getElementById('register-form').classList.remove('hidden');
}

async function handleLogin(event) {
  event.preventDefault();

  const username = document.getElementById('login-username').value;
  const password = document.getElementById('login-password').value;
  const errorEl = document.getElementById('login-error');
  const btn = document.getElementById('login-btn');

  errorEl.classList.add('hidden');
  btn.disabled = true;
  btn.textContent = '로그인 중...';

  try {
    const data = await api('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ username, password })
    });

    state.user = data.user;
    state.token = data.token;
    localStorage.setItem('token', data.token);

    showChat();
    connectSocket();
  } catch (err) {
    errorEl.textContent = err.message;
    errorEl.classList.remove('hidden');
  } finally {
    btn.disabled = false;
    btn.textContent = '로그인';
  }
}

async function handleRegister(event) {
  event.preventDefault();

  const username = document.getElementById('register-username').value;
  const email = document.getElementById('register-email').value;
  const displayName = document.getElementById('register-displayname').value;
  const password = document.getElementById('register-password').value;
  const errorEl = document.getElementById('register-error');
  const btn = document.getElementById('register-btn');

  errorEl.classList.add('hidden');
  btn.disabled = true;
  btn.textContent = '가입 중...';

  try {
    const data = await api('/auth/register', {
      method: 'POST',
      body: JSON.stringify({ username, email, password, displayName })
    });

    state.user = data.user;
    state.token = data.token;
    localStorage.setItem('token', data.token);

    showChat();
    connectSocket();
  } catch (err) {
    errorEl.textContent = err.message;
    errorEl.classList.remove('hidden');
  } finally {
    btn.disabled = false;
    btn.textContent = '가입하기';
  }
}

async function logout() {
  try {
    await api('/auth/logout', { method: 'POST' });
  } catch (err) {
    console.error('Logout error:', err);
  }

  state.user = null;
  state.token = null;
  localStorage.removeItem('token');

  if (state.socket) {
    state.socket.disconnect();
    state.socket = null;
  }

  showAuth();
}

async function checkAuth() {
  const token = localStorage.getItem('token');

  if (!token) {
    showAuth();
    return;
  }

  state.token = token;

  try {
    const data = await api('/auth/me');
    state.user = data;
    showChat();
    connectSocket();
  } catch (err) {
    localStorage.removeItem('token');
    showAuth();
  }
}

// ==================== Screen Navigation ====================
function hideLoading() {
  document.getElementById('loading-screen').classList.add('hidden');
}

function showAuth() {
  hideLoading();
  document.getElementById('auth-screen').classList.remove('hidden');
  document.getElementById('chat-screen').classList.add('hidden');
}

function showChat() {
  hideLoading();
  document.getElementById('auth-screen').classList.add('hidden');
  document.getElementById('chat-screen').classList.remove('hidden');
  document.getElementById('current-user-name').textContent = state.user.displayName || state.user.username;

  loadChannels();
  loadUsers();
}

// ==================== Socket.io Connection ====================
function connectSocket() {
  if (state.socket) return;

  state.socket = io({
    auth: { token: state.token },
    transports: ['websocket', 'polling']
  });

  // Connection events
  state.socket.on('connect', () => {
    console.log('Connected to server');
    showToast('연결됨', 'success');
  });

  state.socket.on('disconnect', () => {
    console.log('Disconnected from server');
    showToast('연결이 끊어졌습니다', 'error');
  });

  state.socket.on('connect_error', (err) => {
    console.error('Connection error:', err);
    if (err.message === 'Authentication required' || err.message === 'Invalid or expired session') {
      logout();
    }
  });

  // User events
  state.socket.on('users:online', (users) => {
    state.onlineUsers.clear();
    users.forEach(u => state.onlineUsers.add(u.id));
    updateUsersList();
  });

  state.socket.on('user:online', (data) => {
    state.onlineUsers.add(data.userId);
    updateUsersList();
  });

  state.socket.on('user:offline', (data) => {
    state.onlineUsers.delete(data.userId);
    updateUsersList();
  });

  state.socket.on('user:status', (data) => {
    if (data.status === 'offline') {
      state.onlineUsers.delete(data.userId);
    } else {
      state.onlineUsers.add(data.userId);
    }
    updateUsersList();
  });

  // Channel events
  state.socket.on('channel:created', (channel) => {
    state.channels.push(channel);
    renderChannels();
  });

  state.socket.on('channel:user_joined', (data) => {
    // Update UI if needed
  });

  // Message events
  state.socket.on('message:new', (message) => {
    if (state.currentChannel && message.channelId === state.currentChannel.id) {
      appendMessage(message);
      scrollToBottom();
    }
  });

  state.socket.on('message:edited', (data) => {
    updateMessageInUI(data);
  });

  state.socket.on('message:deleted', (data) => {
    removeMessageFromUI(data.id);
  });

  state.socket.on('reaction:updated', (data) => {
    updateMessageReactions(data.messageId, data.reactions);
  });

  // Typing events
  state.socket.on('typing:update', (data) => {
    if (state.currentChannel && data.channelId === state.currentChannel.id) {
      showTypingIndicator(data.users);
    }
  });

  // DM events
  state.socket.on('dm:new', (message) => {
    if (state.currentDM &&
        (message.sender.id === state.currentDM.id || message.receiver.id === state.currentDM.id)) {
      appendDMMessage(message);
      scrollToBottom();
    } else {
      // Update unread count
      const senderId = message.sender.id;
      const count = (state.unreadDMs.get(senderId) || 0) + 1;
      state.unreadDMs.set(senderId, count);
      renderDMList();
    }
  });

  state.socket.on('dm:notification', (data) => {
    if (!state.currentDM || state.currentDM.id !== data.from.id) {
      showNotification(`${data.from.displayName || data.from.username}: ${data.preview}`);
    }
  });

  state.socket.on('dm:typing', (data) => {
    if (state.currentDM && data.userId === state.currentDM.id) {
      if (data.isTyping) {
        showTypingIndicator([{ displayName: data.username }]);
      } else {
        hideTypingIndicator();
      }
    }
  });
}

// ==================== Channels ====================
async function loadChannels() {
  try {
    const channels = await api('/channels');
    state.channels = channels;
    renderChannels();

    // Select first channel by default
    if (channels.length > 0) {
      selectChannel(channels[0]);
    }
  } catch (err) {
    console.error('Load channels error:', err);
  }
}

function renderChannels() {
  const container = document.getElementById('channels-list');
  container.innerHTML = state.channels.map(channel => `
    <div class="channel-item ${state.currentChannel?.id === channel.id ? 'active' : ''}"
         onclick="selectChannel(${JSON.stringify(channel).replace(/"/g, '&quot;')})">
      ${channel.name}
    </div>
  `).join('');
}

async function selectChannel(channel) {
  state.currentChannel = channel;
  state.currentDM = null;

  // Update UI
  document.getElementById('chat-icon').textContent = '#';
  document.getElementById('chat-title').textContent = channel.name;
  document.getElementById('chat-description').textContent = channel.description || '';

  renderChannels();
  renderDMList();

  // Join channel room
  state.socket?.emit('channel:join', channel.id);

  // Load messages
  await loadChannelMessages(channel.id);
}

async function loadChannelMessages(channelId) {
  try {
    const data = await api(`/channels/${channelId}/messages`);
    state.messages = data.messages;
    renderMessages();
    scrollToBottom();
  } catch (err) {
    console.error('Load messages error:', err);
  }
}

function showCreateChannel() {
  const modal = document.createElement('div');
  modal.className = 'modal-overlay';
  modal.id = 'create-channel-modal';
  modal.innerHTML = `
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="text-lg font-bold text-white">채널 만들기</h3>
        <button onclick="closeModal('create-channel-modal')" class="text-dark-text-muted hover:text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <div class="modal-body space-y-4">
        <div>
          <label class="form-label">채널 이름</label>
          <input type="text" id="new-channel-name" class="form-input" placeholder="예: project-discussion">
        </div>
        <div>
          <label class="form-label">설명 (선택)</label>
          <input type="text" id="new-channel-desc" class="form-input" placeholder="이 채널의 목적">
        </div>
      </div>
      <div class="modal-footer">
        <button onclick="closeModal('create-channel-modal')" class="btn-secondary">취소</button>
        <button onclick="createChannel()" class="btn-primary">만들기</button>
      </div>
    </div>
  `;
  document.getElementById('modals').appendChild(modal);
}

async function createChannel() {
  const name = document.getElementById('new-channel-name').value.trim();
  const description = document.getElementById('new-channel-desc').value.trim();

  if (!name) {
    showToast('채널 이름을 입력하세요', 'error');
    return;
  }

  state.socket?.emit('channel:create', { name, description }, (response) => {
    if (response.error) {
      showToast(response.error, 'error');
    } else {
      closeModal('create-channel-modal');
      selectChannel(response.channel);
    }
  });
}

// ==================== Users & DMs ====================
async function loadUsers() {
  try {
    const users = await api('/users');
    state.users = users.filter(u => u.id !== state.user.id);
    updateUsersList();
    renderDMList();
  } catch (err) {
    console.error('Load users error:', err);
  }
}

function updateUsersList() {
  // Update online status in user list
  renderDMList();
}

function renderDMList() {
  const container = document.getElementById('dm-list');
  container.innerHTML = state.users.map(user => {
    const isOnline = state.onlineUsers.has(user.id);
    const unread = state.unreadDMs.get(user.id) || 0;

    return `
      <div class="sidebar-item ${state.currentDM?.id === user.id ? 'active' : ''}"
           onclick='selectDM(${JSON.stringify(user).replace(/'/g, "&#39;")})'>
        <span class="${isOnline ? 'status-online' : 'status-offline'}"></span>
        <span class="flex-1 truncate">${user.displayName || user.username}</span>
        ${unread > 0 ? `<span class="unread-badge">${unread}</span>` : ''}
      </div>
    `;
  }).join('');
}

async function selectDM(user) {
  state.currentDM = user;
  state.currentChannel = null;

  // Update UI
  document.getElementById('chat-icon').innerHTML = `
    <span class="${state.onlineUsers.has(user.id) ? 'status-online' : 'status-offline'}"></span>
  `;
  document.getElementById('chat-title').textContent = user.displayName || user.username;
  document.getElementById('chat-description').textContent = '';

  renderChannels();
  renderDMList();

  // Clear unread
  state.unreadDMs.delete(user.id);
  renderDMList();

  // Join DM room
  state.socket?.emit('dm:join', user.id);
  state.socket?.emit('dm:read', user.id);

  // Load messages
  await loadDMMessages(user.id);
}

async function loadDMMessages(userId) {
  try {
    const data = await api(`/users/${userId}/messages`);
    state.messages = data.messages.map(msg => ({
      ...msg,
      user: msg.sender
    }));
    renderMessages();
    scrollToBottom();
  } catch (err) {
    console.error('Load DM messages error:', err);
  }
}

function showUserList() {
  const modal = document.createElement('div');
  modal.className = 'modal-overlay';
  modal.id = 'user-list-modal';
  modal.innerHTML = `
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="text-lg font-bold text-white">사용자 목록</h3>
        <button onclick="closeModal('user-list-modal')" class="text-dark-text-muted hover:text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <div class="modal-body max-h-96 overflow-y-auto">
        ${state.users.map(user => `
          <div class="user-item" onclick="closeModal('user-list-modal'); selectDM(${JSON.stringify(user).replace(/"/g, '&quot;')})">
            <div class="message-avatar">${(user.displayName || user.username).charAt(0).toUpperCase()}</div>
            <div>
              <div class="font-medium text-white">${user.displayName || user.username}</div>
              <div class="text-sm text-dark-text-muted flex items-center gap-1">
                <span class="${state.onlineUsers.has(user.id) ? 'status-online' : 'status-offline'}"></span>
                ${state.onlineUsers.has(user.id) ? '온라인' : '오프라인'}
              </div>
            </div>
          </div>
        `).join('')}
      </div>
    </div>
  `;
  document.getElementById('modals').appendChild(modal);
}

// ==================== Messages ====================
function renderMessages() {
  const container = document.getElementById('messages-list');
  let html = '';

  state.messages.forEach((msg, i) => {
    const prevMsg = state.messages[i - 1];
    const showHeader = !prevMsg ||
                       prevMsg.user.id !== msg.user.id ||
                       new Date(msg.createdAt) - new Date(prevMsg.createdAt) > 300000;

    html += createMessageHTML(msg, showHeader);
  });

  container.innerHTML = html || `
    <div class="text-center py-8 text-dark-text-muted">
      <p>아직 메시지가 없습니다.</p>
      <p class="text-sm">첫 메시지를 보내보세요!</p>
    </div>
  `;
}

function createMessageHTML(msg, showHeader = true) {
  const time = new Date(msg.createdAt).toLocaleTimeString('ko-KR', {
    hour: '2-digit',
    minute: '2-digit'
  });

  const isOwn = msg.user.id === state.user.id;
  const initial = (msg.user.displayName || msg.user.username).charAt(0).toUpperCase();

  let reactionsHTML = '';
  if (msg.reactions && msg.reactions.length > 0) {
    reactionsHTML = `
      <div class="flex flex-wrap gap-1 mt-1">
        ${msg.reactions.map(r => `
          <span class="emoji-reaction" onclick="toggleReaction('${msg.id}', '${r.emoji}')"
                title="${r.users.join(', ')}">
            ${r.emoji} ${r.count}
          </span>
        `).join('')}
      </div>
    `;
  }

  if (showHeader) {
    return `
      <div class="message-container relative" data-message-id="${msg.id}">
        <div class="message-avatar">${initial}</div>
        <div class="message-content">
          <div class="message-header">
            <span class="message-author">${msg.user.displayName || msg.user.username}</span>
            <span class="message-time">${time}</span>
            ${msg.isEdited ? '<span class="text-xs text-dark-text-muted">(수정됨)</span>' : ''}
          </div>
          <div class="message-text">${escapeHtml(msg.content)}</div>
          ${reactionsHTML}
          ${msg.replyCount > 0 ? `<div class="thread-preview">${msg.replyCount}개의 답글</div>` : ''}
        </div>
        ${isOwn ? createMessageActions(msg.id) : createReactionButton(msg.id)}
      </div>
    `;
  } else {
    return `
      <div class="message-container relative pl-14" data-message-id="${msg.id}">
        <span class="message-time text-xs text-dark-text-muted opacity-0 group-hover:opacity-100 absolute left-5">${time}</span>
        <div class="message-content">
          <div class="message-text">${escapeHtml(msg.content)}</div>
          ${reactionsHTML}
        </div>
        ${isOwn ? createMessageActions(msg.id) : createReactionButton(msg.id)}
      </div>
    `;
  }
}

function createMessageActions(messageId) {
  return `
    <div class="message-actions">
      <button class="message-action-btn" onclick="addReactionPicker('${messageId}')" title="리액션">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </button>
      <button class="message-action-btn" onclick="editMessage('${messageId}')" title="수정">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
      </button>
      <button class="message-action-btn" onclick="deleteMessage('${messageId}')" title="삭제">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
      </button>
    </div>
  `;
}

function createReactionButton(messageId) {
  return `
    <div class="message-actions">
      <button class="message-action-btn" onclick="addReactionPicker('${messageId}')" title="리액션">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </button>
    </div>
  `;
}

function appendMessage(msg) {
  const container = document.getElementById('messages-list');
  const lastMsg = state.messages[state.messages.length - 1];
  const showHeader = !lastMsg ||
                     lastMsg.user.id !== msg.user.id ||
                     new Date(msg.createdAt) - new Date(lastMsg.createdAt) > 300000;

  state.messages.push(msg);

  const placeholder = container.querySelector('.text-center');
  if (placeholder) {
    container.innerHTML = '';
  }

  container.insertAdjacentHTML('beforeend', createMessageHTML(msg, showHeader));
}

function appendDMMessage(msg) {
  appendMessage({
    ...msg,
    user: msg.sender
  });
}

function updateMessageInUI(data) {
  const el = document.querySelector(`[data-message-id="${data.id}"]`);
  if (!el) return;

  const textEl = el.querySelector('.message-text');
  if (textEl) {
    textEl.innerHTML = escapeHtml(data.content);
  }

  // Add edited indicator
  const headerEl = el.querySelector('.message-header');
  if (headerEl && !headerEl.querySelector('.text-xs')) {
    headerEl.insertAdjacentHTML('beforeend', '<span class="text-xs text-dark-text-muted">(수정됨)</span>');
  }

  // Update in state
  const msg = state.messages.find(m => m.id === data.id);
  if (msg) {
    msg.content = data.content;
    msg.isEdited = true;
  }
}

function removeMessageFromUI(messageId) {
  const el = document.querySelector(`[data-message-id="${messageId}"]`);
  if (el) {
    el.remove();
  }

  state.messages = state.messages.filter(m => m.id !== messageId);
}

function updateMessageReactions(messageId, reactions) {
  const msg = state.messages.find(m => m.id === messageId);
  if (msg) {
    msg.reactions = reactions;
  }

  const el = document.querySelector(`[data-message-id="${messageId}"]`);
  if (!el) return;

  let reactionsContainer = el.querySelector('.flex-wrap');

  if (reactions.length === 0) {
    if (reactionsContainer) {
      reactionsContainer.remove();
    }
    return;
  }

  const reactionsHTML = reactions.map(r => `
    <span class="emoji-reaction" onclick="toggleReaction('${messageId}', '${r.emoji}')"
          title="${r.users.join(', ')}">
      ${r.emoji} ${r.count}
    </span>
  `).join('');

  if (reactionsContainer) {
    reactionsContainer.innerHTML = reactionsHTML;
  } else {
    const textEl = el.querySelector('.message-text');
    if (textEl) {
      textEl.insertAdjacentHTML('afterend', `<div class="flex flex-wrap gap-1 mt-1">${reactionsHTML}</div>`);
    }
  }
}

// ==================== Message Actions ====================
function handleMessageKeydown(event) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault();
    sendMessage();
  }
}

function handleTyping() {
  const input = document.getElementById('message-input');
  const sendBtn = document.getElementById('send-btn');

  sendBtn.disabled = !input.value.trim();

  // Auto-resize textarea
  input.style.height = 'auto';
  input.style.height = Math.min(input.scrollHeight, 150) + 'px';

  // Typing indicator
  if (!state.isTyping) {
    state.isTyping = true;
    if (state.currentChannel) {
      state.socket?.emit('typing:start', state.currentChannel.id);
    } else if (state.currentDM) {
      state.socket?.emit('dm:typing:start', state.currentDM.id);
    }
  }

  clearTimeout(state.typingTimeout);
  state.typingTimeout = setTimeout(() => {
    state.isTyping = false;
    if (state.currentChannel) {
      state.socket?.emit('typing:stop', state.currentChannel.id);
    } else if (state.currentDM) {
      state.socket?.emit('dm:typing:stop', state.currentDM.id);
    }
  }, 2000);
}

function sendMessage() {
  const input = document.getElementById('message-input');
  const content = input.value.trim();

  if (!content) return;

  if (state.currentChannel) {
    state.socket?.emit('message:send', {
      channelId: state.currentChannel.id,
      content
    }, (response) => {
      if (response.error) {
        showToast(response.error, 'error');
      }
    });
  } else if (state.currentDM) {
    state.socket?.emit('dm:send', {
      receiverId: state.currentDM.id,
      content
    }, (response) => {
      if (response.error) {
        showToast(response.error, 'error');
      }
    });
  }

  input.value = '';
  input.style.height = 'auto';
  document.getElementById('send-btn').disabled = true;

  // Clear typing
  state.isTyping = false;
  clearTimeout(state.typingTimeout);
}

function editMessage(messageId) {
  const msg = state.messages.find(m => m.id === messageId);
  if (!msg) return;

  const modal = document.createElement('div');
  modal.className = 'modal-overlay';
  modal.id = 'edit-message-modal';
  modal.innerHTML = `
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="text-lg font-bold text-white">메시지 수정</h3>
        <button onclick="closeModal('edit-message-modal')" class="text-dark-text-muted hover:text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <div class="modal-body">
        <textarea id="edit-message-content" class="form-input" rows="3">${escapeHtml(msg.content)}</textarea>
      </div>
      <div class="modal-footer">
        <button onclick="closeModal('edit-message-modal')" class="btn-secondary">취소</button>
        <button onclick="submitEditMessage('${messageId}')" class="btn-primary">저장</button>
      </div>
    </div>
  `;
  document.getElementById('modals').appendChild(modal);
}

function submitEditMessage(messageId) {
  const content = document.getElementById('edit-message-content').value.trim();

  if (!content) {
    showToast('내용을 입력하세요', 'error');
    return;
  }

  state.socket?.emit('message:edit', { messageId, content }, (response) => {
    if (response.error) {
      showToast(response.error, 'error');
    } else {
      closeModal('edit-message-modal');
    }
  });
}

function deleteMessage(messageId) {
  if (!confirm('이 메시지를 삭제하시겠습니까?')) return;

  state.socket?.emit('message:delete', { messageId }, (response) => {
    if (response.error) {
      showToast(response.error, 'error');
    }
  });
}

// ==================== Reactions ====================
function addReactionPicker(messageId) {
  const emojis = ['👍', '❤️', '😂', '😮', '😢', '🎉', '🔥', '👀'];

  const modal = document.createElement('div');
  modal.className = 'modal-overlay';
  modal.id = 'reaction-picker-modal';
  modal.onclick = (e) => {
    if (e.target === modal) closeModal('reaction-picker-modal');
  };
  modal.innerHTML = `
    <div class="bg-dark-bg border border-dark-border rounded-xl p-4 shadow-lg">
      <div class="flex gap-2">
        ${emojis.map(emoji => `
          <button class="text-2xl hover:scale-125 transition-transform p-1"
                  onclick="addReaction('${messageId}', '${emoji}')">
            ${emoji}
          </button>
        `).join('')}
      </div>
    </div>
  `;
  document.getElementById('modals').appendChild(modal);
}

function addReaction(messageId, emoji) {
  closeModal('reaction-picker-modal');

  state.socket?.emit('reaction:add', { messageId, emoji }, (response) => {
    if (response.error) {
      showToast(response.error, 'error');
    }
  });
}

function toggleReaction(messageId, emoji) {
  const msg = state.messages.find(m => m.id === messageId);
  if (!msg) return;

  const reaction = msg.reactions?.find(r => r.emoji === emoji);
  const hasReacted = reaction?.users.includes(state.user.username);

  if (hasReacted) {
    state.socket?.emit('reaction:remove', { messageId, emoji });
  } else {
    state.socket?.emit('reaction:add', { messageId, emoji });
  }
}

// ==================== Typing Indicator ====================
function showTypingIndicator(users) {
  if (!users || users.length === 0) {
    hideTypingIndicator();
    return;
  }

  const container = document.getElementById('typing-indicator');
  const usersEl = document.getElementById('typing-users');

  const names = users.map(u => u.displayName || u.username).join(', ');
  usersEl.textContent = `${names}님이 입력 중...`;

  container.classList.remove('hidden');
}

function hideTypingIndicator() {
  document.getElementById('typing-indicator').classList.add('hidden');
}

// ==================== UI Helpers ====================
function scrollToBottom() {
  const container = document.getElementById('messages-container');
  setTimeout(() => {
    container.scrollTop = container.scrollHeight;
  }, 50);
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.remove();
  }
}

function toggleSection(section) {
  const arrow = document.getElementById(`${section}-arrow`);
  const list = document.getElementById(`${section === 'channels' ? 'channels' : 'dm'}-list`);

  arrow.classList.toggle('-rotate-90');
  list.classList.toggle('hidden');
}

function showProfileMenu() {
  const modal = document.createElement('div');
  modal.className = 'modal-overlay';
  modal.id = 'profile-menu-modal';
  modal.onclick = (e) => {
    if (e.target === modal) closeModal('profile-menu-modal');
  };
  modal.innerHTML = `
    <div class="modal-content w-80">
      <div class="p-4 border-b border-dark-border">
        <div class="flex items-center gap-3">
          <div class="message-avatar w-12 h-12 text-xl">
            ${(state.user.displayName || state.user.username).charAt(0).toUpperCase()}
          </div>
          <div>
            <div class="font-bold text-white">${state.user.displayName || state.user.username}</div>
            <div class="text-sm text-dark-text-muted flex items-center gap-1">
              <span class="status-online"></span>
              온라인
            </div>
          </div>
        </div>
      </div>
      <div class="p-2">
        <button onclick="closeModal('profile-menu-modal')" class="w-full text-left px-4 py-2 hover:bg-dark-hover rounded-lg text-dark-text">
          프로필 편집
        </button>
        <button onclick="closeModal('profile-menu-modal'); logout()" class="w-full text-left px-4 py-2 hover:bg-dark-hover rounded-lg text-slack-red">
          로그아웃
        </button>
      </div>
    </div>
  `;
  document.getElementById('modals').appendChild(modal);
}

function showMembersList() {
  const sidebar = document.getElementById('members-sidebar');
  sidebar.classList.toggle('hidden');

  if (!sidebar.classList.contains('hidden')) {
    // Load members
    const container = document.getElementById('members-list');

    if (state.currentChannel) {
      const members = state.users.filter(u => state.onlineUsers.has(u.id));
      const offline = state.users.filter(u => !state.onlineUsers.has(u.id));

      container.innerHTML = `
        <div class="mb-4">
          <h4 class="text-sm font-medium text-dark-text-muted mb-2">온라인 - ${members.length}</h4>
          ${members.map(u => `
            <div class="user-item">
              <span class="status-online"></span>
              <span>${u.displayName || u.username}</span>
            </div>
          `).join('')}
        </div>
        <div>
          <h4 class="text-sm font-medium text-dark-text-muted mb-2">오프라인 - ${offline.length}</h4>
          ${offline.map(u => `
            <div class="user-item">
              <span class="status-offline"></span>
              <span class="text-dark-text-muted">${u.displayName || u.username}</span>
            </div>
          `).join('')}
        </div>
      `;

      document.getElementById('members-count').textContent = state.users.length + 1;
    }
  }
}

function handleSearch(event) {
  const query = event.target.value.trim();
  // Implement search functionality
  console.log('Search:', query);
}

function insertEmoji() {
  const emojis = ['😀', '😂', '❤️', '👍', '🎉', '🔥', '✨', '💯', '🙏', '👀'];

  const modal = document.createElement('div');
  modal.className = 'modal-overlay';
  modal.id = 'emoji-picker-modal';
  modal.onclick = (e) => {
    if (e.target === modal) closeModal('emoji-picker-modal');
  };
  modal.innerHTML = `
    <div class="bg-dark-bg border border-dark-border rounded-xl p-4 shadow-lg">
      <div class="grid grid-cols-5 gap-2">
        ${emojis.map(emoji => `
          <button class="text-2xl hover:scale-125 transition-transform p-2"
                  onclick="insertEmojiToInput('${emoji}')">
            ${emoji}
          </button>
        `).join('')}
      </div>
    </div>
  `;
  document.getElementById('modals').appendChild(modal);
}

function insertEmojiToInput(emoji) {
  closeModal('emoji-picker-modal');
  const input = document.getElementById('message-input');
  input.value += emoji;
  input.focus();
  handleTyping();
}

function attachFile() {
  showToast('파일 첨부 기능은 준비 중입니다', 'info');
}

// ==================== Notifications ====================
function showToast(message, type = 'info') {
  const toast = document.createElement('div');
  toast.className = `toast ${type === 'success' ? 'toast-success' : type === 'error' ? 'toast-error' : ''}`;
  toast.innerHTML = `
    <span>${message}</span>
    <button onclick="this.parentElement.remove()" class="text-dark-text-muted hover:text-white ml-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  `;
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.remove();
  }, 3000);
}

function showNotification(message) {
  if (Notification.permission === 'granted') {
    new Notification('Realtime Chat', { body: message });
  } else if (Notification.permission !== 'denied') {
    Notification.requestPermission();
  }
}

// ==================== Utility Functions ====================
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// ==================== Initialize ====================
document.addEventListener('DOMContentLoaded', () => {
  // Request notification permission
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }

  // Check authentication
  checkAuth();
});
