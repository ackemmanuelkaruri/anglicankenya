        function switchTheme(theme) {
            document.body.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            updateThemeButtons(theme);
        }

        function updateThemeButtons(theme) {
            document.querySelectorAll('.theme-selector button').forEach(btn => {
                btn.classList.remove('active');
            });
            const themeMap = {
                'light': '.light-theme',
                'dark': '.dark-theme',
                'ocean': '.ocean-theme',
                'forest': '.forest-theme'
            };
            document.querySelector(themeMap[theme])?.classList.add('active');
        }

        function openAIChat() {
            document.getElementById('aiChatModal').classList.add('active');
        }

        function closeAIChat() {
            document.getElementById('aiChatModal').classList.remove('active');
        }

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            if (!message) return;

            const messagesDiv = document.getElementById('chatMessages');
            
            // Add user message
            const userMsg = document.createElement('div');
            userMsg.className = 'user-message';
            userMsg.textContent = message;
            messagesDiv.appendChild(userMsg);
            
            input.value = '';
            messagesDiv.scrollTop = messagesDiv.scrollHeight;

            // Simulate AI response
            setTimeout(() => {
                const aiMsg = document.createElement('div');
                aiMsg.className = 'ai-message';
                aiMsg.innerHTML = '<strong>AI Assistant:</strong> Thanks for your question! This is a demo response. In production, this would connect to your AI support system.';
                messagesDiv.appendChild(aiMsg);
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            }, 500);
        }

        function handleChatInput(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        // Load saved theme
        window.addEventListener('load', () => {
            const saved = localStorage.getItem('theme') || 'light';
            switchTheme(saved);
        });

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeAIChat();
            }
        });