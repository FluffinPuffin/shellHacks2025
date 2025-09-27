# Gemini API Setup Instructions


## 1. Set Up Environment Variables

1. Copy the `.env.template` file to `.env`:
   ```bash
   copy .env.template .env
   ```

2. Open the `.env` file and replace `your_gemini_api_key_here` with your actual API key:
   ```
   GEMINI_API_KEY=your_actual_api_key_here
   ```

## 2. Install Dependencies

```bash
pip install -r requirements.txt
```

## 3. Test the Setup

Run the test script to verify everything is working:

```bash
python gemini_client.py
```

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
