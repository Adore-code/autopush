const { createApp, ref, watch, nextTick, onMounted } = Vue

const App = {
    setup() {
        const provider = ref('openai')
        const model = ref('')
        const message = ref('')
        const role = ref('default')
        const useStream = ref(true)
        const loading = ref(false)
        const chatHistory = ref([])
        const usage = ref(null)
        const chatBoxRef = ref(null)

        const modelOptions = {
            openai: [
                { label: 'GPT-3.5 Turbo', value: 'gpt-3.5-turbo' },
                { label: 'GPT-4', value: 'gpt-4-turbo' }
            ],
            deepseek: [
                { label: 'DeepSeek-R1', value: 'deepseek-r1' },
                { label: 'DeepSeek-V3', value: 'deepseek-v3' }
            ]
        }

        watch(provider, () => {
            model.value = modelOptions[provider.value][0].value
        }, { immediate: true })

        const scrollToBottom = () => {
            nextTick(() => {
                const el = chatBoxRef.value
                if (el) el.scrollTop = el.scrollHeight
            })
        }

        const renderMarkdown = (text) => marked.parse(text || '')

        const handleKey = (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault()
                useStream.value ? sendStream() : send()
            }
        }

        const send = async () => {
            if (!message.value.trim()) return
            loading.value = true
            chatHistory.value.push({ role: 'user', content: message.value })

            const res = await fetch('/app/autopush/aichat/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: message.value,
                    role: role.value,
                    provider: provider.value,
                    model: model.value
                })
            })

            const data = await res.json()
            chatHistory.value.push({ role: 'assistant', content: data.data.reply })
            usage.value = data.data.usage
            message.value = ''
            loading.value = false
            scrollToBottom()
        }

        const sendStream = async () => {
            if (!message.value.trim()) return;

            loading.value = true;

            // 添加用户发言
            chatHistory.value.push({ role: 'user', content: message.value });

            // 使用 reactive 包装 assistant 消息，确保内容更新可响应
            const assistantMsg = reactive({ role: 'assistant', content: '' });
            chatHistory.value.push(assistantMsg);

            try {
                const res = await fetch('/app/autopush/aichat/completions', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        provider: provider.value,
                        model: model.value,
                        role: role.value,
                        message: message.value
                    })
                });

                if (!res.ok || !res.body) throw new Error('请求失败，状态码：' + res.status);

                const reader = res.body.getReader();
                const decoder = new TextDecoder('utf-8');
                let buffer = '';
                let fullText = '';
                let flushPending = false;

                const flushUI = () => {
                    if (flushPending) return;
                    flushPending = true;
                    requestAnimationFrame(() => {
                        assistantMsg.content = fullText;
                        scrollToBottom();
                        flushPending = false;
                    });
                };

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop(); // 保留未完整一行

                    for (let line of lines) {
                        line = line.trim();
                        if (!line || line === '[DONE]' || line === 'data: [DONE]') continue;

                        if (line.startsWith('data:')) {
                            line = line.replace(/^data:\s*/, '');
                        }

                        try {
                            const json = JSON.parse(line);
                            const delta = json?.choices?.[0]?.delta?.content;
                            if (delta) {
                                fullText += delta;
                                flushUI(); // 用 raf 控制刷新
                            }
                        } catch (err) {
                            console.warn('解析失败：', line, err);
                        }
                    }
                }

                message.value = '';
            } catch (err) {
                console.error('流接收异常：', err);
                ElMessage.error('发生错误，请稍后重试。');
            } finally {
                loading.value = false;
            }
        };

        const clear = async () => {
            await ElementPlus.ElMessageBox.confirm('确认清除当前对话？', '提示', { type: 'warning' })
            await fetch('/app/autopush/aichat/clear', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ role: role.value })
            })
            chatHistory.value = []
            usage.value = null
        }

        onMounted(scrollToBottom)

        return {
            provider, model, message, role, useStream, loading,
            modelOptions, chatHistory, usage, chatBoxRef,
            renderMarkdown, send, sendStream, clear, handleKey
        }
    },
    template: `
    <h2 style="margin-bottom: 10px;">🤖 多平台模型对话系统</h2>

    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
      <el-select v-model="provider" style="width: 160px;">
        <el-option label="OpenAI" value="openai" />
        <el-option label="DeepSeek" value="deepseek" />
      </el-select>

      <el-select v-model="model" style="width: 180px;">
        <el-option v-for="m in modelOptions[provider]" :label="m.label" :value="m.value" />
      </el-select>

      <el-select v-model="role" style="width: 140px;">
        <el-option label="默认助手" value="default" />
        <el-option label="程序员" value="coder" />
        <el-option label="作家" value="writer" />
        <el-option label="客服" value="support" />
      </el-select>

      <el-switch v-model="useStream" active-text="流式模式" inactive-text="普通模式" />
    </div>

    <div ref="chatBoxRef" class="chat-box">
      <div v-for="(msg, i) in chatHistory" :key="i" :class="['chat-message', msg.role === 'user' ? 'user' : 'assistant']">
        <div v-html="renderMarkdown(msg.content)"></div>
      </div>
    </div>

    <el-input
      v-model="message"
      type="textarea"
      rows="4"
      placeholder="输入内容，Enter 发送，Shift+Enter 换行"
      @keydown="handleKey"
      style="margin-bottom: 10px;"
    />

    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
      <el-button type="primary" @click="useStream ? sendStream() : send()" :loading="loading">发送</el-button>
      <el-button type="danger" @click="clear">清除对话</el-button>
    </div>

    <div v-if="usage">
      <el-tag type="info">prompt: {{ usage.prompt_tokens }}</el-tag>
      <el-tag type="success" style="margin-left: 10px;">completion: {{ usage.completion_tokens }}</el-tag>
      <el-tag type="warning" style="margin-left: 10px;">total: {{ usage.total_tokens }}</el-tag>
    </div>
  `
}

createApp(App).use(ElementPlus).mount('#app')

marked.setOptions({
    highlight: code => hljs.highlightAuto(code).value
})
