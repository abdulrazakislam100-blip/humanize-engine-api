import os
from fastapi import FastAPI
from pydantic import BaseModel
from openai import OpenAI

client = OpenAI(api_key=os.environ["OPENAI_API_KEY"])

app = FastAPI()

class TextIn(BaseModel):
    text: str

class TextOut(BaseModel):
    humanized_text: str

SYSTEM_PROMPT = (
    "You rewrite AI-generated English text so it sounds like natural human writing. "
    "Keep the original meaning, avoid obvious AI phrasing, vary sentence length, "
    "and do not invent new facts."
)

@app.post("/humanize", response_model=TextOut)
async def humanize_text(body: TextIn):
    user_text = body.text.strip()
    response = client.chat.completions.create(
        model="gpt-4.1-mini",  # or 'o3-mini' if enabled on your account
        messages=[
            {"role": "system", "content": SYSTEM_PROMPT},
            {
                "role": "user",
                "content": f"Rewrite this text to sound naturally human:\n\n{user_text}"
            },
        ],
        temperature=0.7,
    )
    out = response.choices[0].message.content.strip()
    return TextOut(humanized_text=out)
