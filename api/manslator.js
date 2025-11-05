export default async function handler(req, res) {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "POST, OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type");
  res.setHeader("Content-Type", "application/json");

  if (req.method === "OPTIONS") {
    res.status(200).end();
    return;
  }

  if (req.method !== "POST") {
    res.status(405).json({ error: "Method not allowed" });
    return;
  }

  const apiKey = process.env.GROQ_API_KEY;
  if (!apiKey) {
    res.status(500).json({ error: "API key not configured" });
    return;
  }

  const model = process.env.GROQ_MODEL || "openai/gpt-oss-120b";
  const temperature = parseFloat(process.env.GROQ_TEMPERATURE ?? "1.0");
  const topP = parseFloat(process.env.GROQ_TOP_P ?? "1.0");
  const maxCompletionTokens = parseInt(process.env.GROQ_MAX_COMPLETION_TOKENS ?? "8192", 10);
  const reasoningEffort = process.env.GROQ_REASONING_EFFORT;

  let payload;
  try {
    payload = typeof req.body === "string" ? JSON.parse(req.body) : req.body;
  } catch (error) {
    res.status(400).json({ error: "Invalid JSON" });
    return;
  }

  const userText = typeof payload?.text === "string" ? payload.text.trim() : "";
  if (!userText) {
    res.status(400).json({ error: "No input text provided" });
    return;
  }

  if (userText.length > 500) {
    res.status(400).json({ error: "Text too long (max 500 characters)" });
    return;
  }

  const systemPrompt = `You are Manslator — a sharp, emotionally aware translator who decodes what women really mean and replies with witty, confident, and smooth comebacks.

Mission:
1. Diagnose the hidden subtext and emotional intent behind her words.
2. Craft a short, human, irresistibly confident reply that mirrors her vibe.
3. Share one concise micro-advice insight so the user knows how to deliver the reply.

Language:
- Always speak in Hinglish — a natural blend of Hindi and English that sounds authentic, playful, and contemporary.
- Preserve any Hinglish phrasing from the user where it helps the flow, and avoid pure English unless the tone demands it.

Output contract:
- Return ONLY a valid JSON object — no prose, markdown, or extra keys.
- JSON schema (all string values):
    {
        "what_she_said": original text (keep pronouns & tone),
        "what_she_means": decoded meaning with emotional context,
        "smart_reply": the exact reply line to send back,
        "tone": one of ["playful", "affectionate", "bold", "supportive", "candid", "reassuring"],
        "confidence": one of ["low", "medium", "high"],
        "micro_advice": tiny coaching tip (<= 120 characters)
    }
- Keep everything punchy, natural, and no longer than two sentences per field (except the reply, which can be one or two lines max).
- Avoid emojis unless they elevate the vibe. Never be cringe, needy, or generic.

Reference playbook (calibrate tone & wit — do NOT copy verbatim, but stay in this league):
1. Q: "Me or the most beautiful girl in the world?" → A: "The most beautiful girl in the world… kyunki wo tu hi toh hai. 😌"
2. Q: "Would you date your ex if we breakup?" → A: "Haan, kyunki tu hi meri ex banegi. 😉"
3. Q: "Who do you love more — me or yourself?" → A: "Mujhe apne aap se bhi zyada tu pasand hai. ❤️"
4. Q: "If I go away forever?" → A: "Tab bhi tu mere har khayal ke beech mein hogi."
5. Q: "Who’s your crush right now?" → A: "Same as yesterday — tu. 😎"
6. Q: "If you had one wish?" → A: "Tera 'always' mil jaaye bas. 💫"
7. Q: "What if I fall for someone else?" → A: "Firse uth ke mere paas aana. 😉"
8. Q: "Main zyada pretty lagti hoon ya smart?" → A: "Pretty bhi tu, smart bhi tu — matlab overpowered character hai tu. 😅"
9. Q: "Tum mujhe deserve karte ho?" → A: "Karna nahi padta, bas feel hota hai."
10. Q: "If I say 'I hate you'?" → A: "Phir bhi sunna accha lagega, kyunki tu hi keh rahi hai."

If the user text lacks context, infer confidently but stay grounded. Never lecture. Never downplay her feelings. Stay slick.`;

  const body = {
    model,
    temperature: Number.isFinite(temperature) ? temperature : 1.0,
    top_p: Number.isFinite(topP) ? topP : 1.0,
    max_completion_tokens: Number.isInteger(maxCompletionTokens) ? maxCompletionTokens : 8192,
    stream: false,
    stop: null,
    response_format: { type: "json_object" },
    messages: [
      { role: "system", content: systemPrompt },
      { role: "user", content: userText }
    ]
  };

  if (reasoningEffort) {
    body.reasoning_effort = reasoningEffort;
  }

  let groqResponse;
  try {
    groqResponse = await fetch("https://api.groq.com/openai/v1/chat/completions", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${apiKey}`
      },
      body: JSON.stringify(body)
    });
  } catch (error) {
    res.status(500).json({ error: `API request failed: ${error.message}` });
    return;
  }

  let groqPayload;
  const status = groqResponse.status;
  const rawGroqBody = await groqResponse.text();

  if (rawGroqBody) {
    try {
      groqPayload = JSON.parse(rawGroqBody);
    } catch (error) {
      res.status(500).json({ error: "Invalid API response" });
      return;
    }
  }

  if (!groqResponse.ok) {
    const details = groqPayload?.error?.message || groqPayload?.message || rawGroqBody?.slice(0, 600) || "Unknown error";
    res.status(status).json({ error: `API returned error code: ${status}`, details });
    return;
  }

  const reply = groqPayload?.choices?.[0]?.message?.content;
  if (!reply) {
    res.status(500).json({ error: "No response from AI" });
    return;
  }

  let structuredReply;
  try {
    structuredReply = JSON.parse(reply);
  } catch (error) {
    res.status(500).json({ error: "AI returned invalid JSON", raw: reply });
    return;
  }

  const normalize = (value) => (typeof value === "string" ? value.trim() : "");

  res.status(200).json({
    reply: {
      whatSheSaid: normalize(structuredReply.what_she_said ?? userText),
      whatSheMeans: normalize(structuredReply.what_she_means),
      smartReply: normalize(structuredReply.smart_reply),
      tone: normalize(structuredReply.tone),
      confidence: normalize(structuredReply.confidence),
      microAdvice: normalize(structuredReply.micro_advice)
    }
  });
}
