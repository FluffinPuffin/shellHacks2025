import google.generativeai as genai
from config import Config

class GeminiClient:
    """Client for interacting with Google's Gemini API"""
    
    def __init__(self):
        """Initialize the Gemini client with API key"""
        Config.validate_config()
        genai.configure(api_key=Config.GEMINI_API_KEY)
        self.model = genai.GenerativeModel('gemini-2.5-flash')
    
    def generate_response(self, prompt: str) -> str:
        """
        Generate a response using Gemini API
        
        Args:
            prompt (str): The input prompt for the AI
            
        Returns:
            str: The generated response
        """
        try:
            response = self.model.generate_content(prompt)
            return response.text
        except Exception as e:
            return f"Error generating response: {str(e)}"
    
    def analyze_budget_data(self, budget_data: str) -> str:
        """
        Analyze budget data and provide insights
        
        Args:
            budget_data (str): Budget information to analyze
            
        Returns:
            str: Analysis and recommendations
        """
        prompt = f"""
        As an AI budget advisor, analyze the following budget data and provide insights:
        
        {budget_data}
        
        Please provide:
        1. Key observations about spending patterns
        2. Areas where money could be saved
        3. Recommendations for budget optimization
        4. Potential financial goals to consider
        
        Keep the response concise and actionable.
        """
        
        return self.generate_response(prompt)
    
    def find_budget_apps(self, requirements: str) -> str:
        """
        Find budget apps based on user requirements
        
        Args:
            requirements (str): User's requirements for a budget app
            
        Returns:
            str: Recommended budget apps with explanations
        """
        prompt = f"""
        As an AI assistant specializing in personal finance apps, recommend budget apps based on these requirements:
        
        {requirements}
        
        Please provide:
        1. Top 3-5 budget app recommendations
        2. Brief description of each app's key features
        3. Pros and cons of each recommendation
        4. Which app would be best for the user's specific needs
        
        Focus on popular, well-reviewed apps that are currently available.
        """
        
        return self.generate_response(prompt)

# Example usage
if __name__ == "__main__":
    try:
        client = GeminiClient()
        
        # Test basic functionality
        print("Testing Gemini API connection...")
        response = client.generate_response("Hello! Can you help me with budget planning?")
        print(f"Response: {response}")
        
        # Test budget analysis
        print("\n" + "="*50)
        print("Testing budget analysis...")
        sample_budget = """
        Monthly Income: $5000
        Rent: $1500
        Groceries: $600
        Transportation: $400
        Entertainment: $300
        Savings: $500
        """
        analysis = client.analyze_budget_data(sample_budget)
        print(f"Analysis: {analysis}")
        
    except Exception as e:
        print(f"Error: {e}")
        print("Make sure you have set your GEMINI_API_KEY in the .env file")
