# Manslator Pro 💬

A witty AI-powered translator that decodes what women really mean and provides clever, confident replies.

## Features ✨

- **Smart Translation** - Decodes hidden meanings and emotions in text
- **Witty Replies** - Generates clever, confident comebacks that match the vibe
- **Modern UI** - Beautiful, responsive design with smooth animations
- **Dark Mode HQ** - Cinematic night-mode with panelized insights
- **Quick Prompt Menu** - Hamburger playbook with witty presets
- **Error Handling** - Robust error handling and user feedback
- **Mobile Friendly** - Works perfectly on all devices

## How It Works 🧠

1. Type what she said
2. AI analyzes the subtext and emotion
3. Get a decoded meaning + perfect reply
4. Use Ctrl+Enter for quick translation

## Tech Stack 🛠️

- **Frontend**: HTML5, CSS3 (with glassmorphism design), Vanilla JavaScript
- **Backend (local)**: PHP with cURL for API calls (`manslator.php`)
- **Backend (Vercel)**: Serverless Node handler (`api/manslator.js`)
- **AI**: Groq API with Llama 3 model family
- **Deployment**: Vercel (primary) or any PHP-enabled host

## Setup 🚀

1. Copy `.env.example` to `.env` and fill in your Groq credentials
2. (Optional) Adjust temperature, top_p, max tokens, or reasoning effort in `.env`
3. **Local (XAMPP/PHP)**: Serve the repo root so `manslator.php` is available at `/manslator.php`
4. **Vercel**: Deploy the project (Node 18 runtime). The serverless function `api/manslator.js` is automatically used
5. Ensure `.env` is added to Vercel project settings (Environment Variables tab)
6. Access via web browser

## Environment Variables

```
GROQ_API_KEY=your_groq_api_key_here
GROQ_MODEL=openai/gpt-oss-120b
GROQ_TEMPERATURE=1
GROQ_TOP_P=1
GROQ_MAX_COMPLETION_TOKENS=8192
GROQ_REASONING_EFFORT=medium
```

## API Endpoints

- `POST /api/manslator` → Vercel serverless function (preferred for deployment)
- `POST /api/manslator.php` or `/manslator.php` → PHP fallback when running on traditional hosting

Body: `{"text": "what she said"}`

Response:

```json
{
	"reply": {
		"whatSheSaid": "...",
		"whatSheMeans": "...",
		"smartReply": "...",
		"tone": "...",
		"confidence": "...",
		"microAdvice": "..."
	}
}
```

## Features Added in This Version

- ✅ Glassmorphism UI design
- ✅ Responsive mobile layout
- ✅ Loading animations
- ✅ Error handling with user-friendly messages
- ✅ Keyboard shortcuts (Ctrl+Enter)
- ✅ Auto-resizing textarea
- ✅ Rate limiting (500 char max)
- ✅ Input validation
- ✅ Smooth transitions and animations
- ✅ Modern gradient backgrounds

Enjoy using Manslator Pro! 😎
