const systemPrompt = "test";
const userPrompt = "test";

fetch("https://openrouter.ai/api/v1/chat/completions", {
  method: "POST",
  headers: {
    "Authorization": "Bearer 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",
    "Content-Type": "application/json"
  },
  body: JSON.stringify({
    "model": "openrouter/auto", // I'll test routing first
    "models": ["nvidia/nemotron-3-ultra-550b-a55b:free", "minimax/minimax-m3:free", "nvidia/llama-3.1-nemotron-70b-instruct:free", "meta-llama/llama-3.3-70b-instruct:free"],
    "messages": [
      { "role": "system", "content": systemPrompt },
      { "role": "user", "content": userPrompt }
    ]
  })
})
.then(response => response.json())
.then(data => console.log(JSON.stringify(data)))
.catch(err => console.error(err));
