
# 🧠 Manslator Pro  
### Decode what she says. Reply like you’ve actually got game.

---

**Manslator Pro** is a small web app that takes what she texts, figures out what she *really* means, and gives you a confident, clever reply that sounds human — not AI.  

Because half the time she’s not asking a question… she’s *testing something*.  
This app just saves you the mental gymnastics.

---

## ⚙️ What it does
You type something like:

> “If we break up, would you go back to your ex?”

and Manslator Pro gives you:

```

What She Meant: She’s testing if she still matters more than anyone before.
Smart Reply: “Yeah, ‘cause you’d be my ex then — so technically, yes 😉”

````

Straight, sharp, and smooth. No cringe pickup lines. No overthinking.

---

## 🧩 Tech Stuff
- **Backend:** PHP (serverless function on Vercel)
- **AI Engine:** [Groq API](https://groq.com) — model `openai/gpt-oss-120b`
- **Frontend:** Plain HTML + JS (light, minimal)
- **Hosting:** [Vercel](https://vercel.com)
- **Secrets:** API key stored safely in environment variable (`GROQ_API_KEY`)

---

## 🚀 Setup

### 1. Clone this repo
```bash
git clone https://github.com/yourusername/manslator-pro.git
cd manslator-pro
````

### 2. Folder structure

```
manslator-pro/
  ├── api/
  │   └── manslator.php
  ├── index.html
  └── vercel.json
```

### 3. Add your Groq API key in Vercel

1. Go to your project on [Vercel](https://vercel.com)
2. **Settings → Environment Variables → Add New**

   * Name: `GROQ_API_KEY`
   * Value: your actual Groq API key (starts with `gsk_...`)
   * Environment: Production, Preview, Development
3. Save.

Your PHP file automatically uses it:

```php
$api_key = getenv("GROQ_API_KEY");
```

### 4. Deploy

```bash
vercel
```

done. your app’s live at something like:

```
https://manslator-pro.vercel.app
```

---

## 💡 Why this exists

Because texts aren’t always about the words.
Sometimes “I’m fine” means *you better fix this*.
Sometimes “you’ve changed” means *I miss the old energy*.

Manslator Pro just decodes it and helps you respond like someone emotionally fluent, not clueless.

---

## 🧠 Example Lines

| What She Said                                   | Manslation                                                           | Smart Reply                                                     |
| ----------------------------------------------- | -------------------------------------------------------------------- | --------------------------------------------------------------- |
| “If we break up, would you go back to your ex?” | She’s checking if she still matters more than anyone from your past. | “Yeah, ‘cause you’d be my ex then — so technically, yes 😌”     |
| “Me or the world’s most beautiful woman?”       | She wants reassurance, but she’s asking playfully.                   | “World’s most beautiful woman — that’s literally you though 😏” |
| “You’ve changed.”                               | She feels the energy drop and misses how it was.                     | “Maybe I stopped proving what you already knew.”                |

---

## 🛠️ Customizing

You can tweak tone or behavior inside `manslator.php` by editing the `$system_prompt`.
Wanna make it savage, flirty, or more emotional? Just change a few lines there — Groq does the rest.

---

## ❤️ Credits

Built by a guy who got tired of decoding texts the hard way.
Powered by **Groq AI**.
Hosted on **Vercel**.
100% open-source, no BS.

---

## 🏷️ Tags

`#groq` `#php` `#vercel` `#manslator` `#aiapp` `#relationships` `#textdecoder`

```

