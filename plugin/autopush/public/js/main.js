const { createApp, ref, onMounted, nextTick } = Vue

const App = {
    setup() {
        const message = ref('')
        const role = ref('default')
        const chatHistory = ref([])
        const usage = ref(null)
        const loading = ref(false)
        const chatBoxRef = ref(null)

        // 自动滚动到底部
        const scrollToBottom = () => {
            nextTick(() => {
                const el = chatBoxRef.value
                if (el) el.scrollTop = el.scrollHeight
            })
        }

        const send = async () => {
            if (!message.value.trim()) return
            loading.value = true
            chatHistory.value.push({ role: 'user', content: message.value })

            const res = await fetch('/app/autopush/ai/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message.value, role: role.value })
            })

            const data = await res.json()
            chatHistory.value.push({ role: 'assistant', content: data.reply })
            usage.value = data.usage
            message.value = ''
            loading.value = false
            scrollToBottom()
        }

        const clear = async () => {
            await ElementPlus.ElMessageBox.confirm('确认清除当前对话？', '提示', { type: 'warning' })
            await fetch('/app/autopush/ai/clear', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ role: role.value })
            })
            chatHistory.value = []
            usage.value = null
        }

        const handleKey = (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault()
                send()
            }
        }

        // 高亮渲染 markdown
        const renderMarkdown = (text) => {
            const html = marked.parse(text || '')
            return html
        }

        onMounted(scrollToBottom)

        return {
            message, role, chatHistory, usage, loading,
            send, clear, handleKey,
            chatBoxRef, renderMarkdown
        }
    },
    template: `
    <h2 style="margin-bottom: 10px;"><d83d><dcac> gpt-4-turbo</h2>

    <!-- 聊天框 -->
    <div ref="chatBoxRef" class="chat-box" style="max-height: 500px; overflow-y: auto; margin-bottom: 15px;">
      <div v-for="(msg, index) in chatHistory" :key="index"
           :class="['chat-message', msg.role === 'user' ? 'user' : 'assistant']">
        <div v-html="renderMarkdown(msg.content)"></div>
      </div>
    </div>

    <!-- 输入区域 -->
    <el-select v-model="role" style="width: 200px; margin-bottom: 10px;">
      <el-option label="默认助手" value="default" />
      <el-option label="程序员" value="coder" />
      <el-option label="作家" value="writer" />
      <el-option label="客服" value="support" />
    </el-select>

    <el-input
      v-model="message"
      type="textarea"
      rows="4"
      placeholder="输入内容，Enter 发送，Shift+Enter 换行"
      @keydown="handleKey"
      style="margin-bottom: 10px;"
    />

    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
      <el-button type="primary" @click="send" :loading="loading">发送</el-button>
      <el-button type="danger" @click="clear">清除对话</el-button>
    </div>

    <!-- Token 统计 -->
    <div v-if="usage">
      <el-tag type="info">prompt: {{ usage.prompt_tokens }}</el-tag>
      <el-tag type="success" style="margin-left: 10px;">completion: {{ usage.completion_tokens }}</el-tag>
      <el-tag type="warning" style="margin-left: 10px;">total: {{ usage.total_tokens }}</el-tag>
    </div>
  `
}

createApp(App).use(ElementPlus).mount('#app')

// 开启 highlight.js 语法高亮
marked.setOptions({
    highlight: function (code, lang) {
        return hljs.highlightAuto(code, [lang]).value
    }
})