import os
from dotenv import load_dotenv

# Load environment variables from .env file
load_dotenv()

class Config:
    """Configuration class for the AI Budget App Locator"""
    
    # Gemini API Configuration
    GEMINI_API_KEY = os.getenv('GEMINI_API_KEY')
    
    @classmethod
    def validate_config(cls):
        """Validate that all required configuration is present"""
        if not cls.GEMINI_API_KEY:
            raise ValueError(
                "GEMINI_API_KEY is not set. Please add your Gemini API key to the .env file.\n"
                "Get your API key from: https://makersuite.google.com/app/apikey"
            )
        return True
