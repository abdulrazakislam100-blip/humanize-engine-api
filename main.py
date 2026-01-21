import os
from fastapi import FastAPI
from pydantic import BaseModel
from openai import OpenAI

def get_client():
    return OpenAI(api_key=os.environ["OPENAI_API_KEY"])
app = FastAPI()

class TextIn(BaseModel):
    text: str

class TextOut(BaseModel):
    humanized_text: str

class ProductBriefIn(BaseModel):
    idea: str

class ProductBriefOut(BaseModel):
    brief: str

SYSTEM_PROMPT = (
    "You rewrite AI-generated English text so it sounds like natural human writing. "
    "Keep the original meaning, avoid obvious AI phrasing, vary sentence length, "
    "and do not invent new facts."
)

PRODUCT_BRIEF_SYSTEM_PROMPT = (
    "You are a product strategist and UX writer. Create a clear, concise product brief "
    "for a web or mobile app idea. Include sections for: Problem, Target users, Core "
    "features, Subscription tiers, MVP scope, Tech stack suggestion, and Next steps. "
    "Keep it practical and easy to scan."
)

@app.post("/humanize", response_model=TextOut)
async def humanize_text(body: TextIn):
    user_text = body.text.strip()
    response = get_client().chat.completions.create(
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

@app.post("/product-brief", response_model=ProductBriefOut)
async def create_product_brief(body: ProductBriefIn):
    idea_text = body.idea.strip()
    response = get_client().chat.completions.create(
        model="gpt-4.1-mini",
        messages=[
            {"role": "system", "content": PRODUCT_BRIEF_SYSTEM_PROMPT},
            {
                "role": "user",
                "content": (
                    "Create a product brief for this app idea:\n\n"
                    f"{idea_text}\n\n"
                    "Assume the app supports multiple barber shops and recurring "
                    "monthly or yearly subscriptions."
                ),
            },
        ],
        temperature=0.6,
    )
    out = response.choices[0].message.content.strip()
    return ProductBriefOut(brief=out)
