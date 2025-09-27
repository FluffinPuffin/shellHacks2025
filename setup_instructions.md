# Gemini API Setup Instructions

## 1. Get Your Gemini API Key

1. Go to [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Sign in with your Google account
3. Click "Create API Key"
4. Copy the generated API key

## 2. Set Up Environment Variables

1. Copy the `.env.template` file to `.env`:
   ```bash
   copy .env.template .env
   ```

2. Open the `.env` file and replace `your_gemini_api_key_here` with your actual API key:
   ```
   GEMINI_API_KEY=your_actual_api_key_here
   ```

## 3. Install Dependencies

```bash
pip install -r requirements.txt
```

## 4. Test the Setup

Run the test script to verify everything is working:

```bash
python gemini_client.py
```

## 5. Security Notes

- Never commit your `.env` file to version control
- The `.gitignore` file is already configured to exclude `.env` files
- Keep your API key secure and don't share it publicly

## Usage Example

```python
from gemini_client import GeminiClient

# Initialize the client
client = GeminiClient()

# Analyze budget data
budget_data = "Monthly income: $5000, Rent: $1500, Groceries: $600"
analysis = client.analyze_budget_data(budget_data)
print(analysis)

# Find budget apps
requirements = "I need an app for tracking expenses and setting savings goals"
recommendations = client.find_budget_apps(requirements)
print(recommendations)
```
