#!/usr/bin/env python3
"""
Python script to call Gemini API for budget cost estimation
This script is called by PHP to get AI-generated cost estimates
"""

import sys
import os
import json
from pathlib import Path

# Add the project root to the Python path
project_root = Path(__file__).parent
sys.path.insert(0, str(project_root))

try:
    from gemini_client import GeminiClient
except ImportError:
    print("Error: Could not import GeminiClient. Make sure gemini_client.py is in the project root.")
    sys.exit(1)

def main():
    if len(sys.argv) != 2:
        print("Error: Usage: python call_gemini.py <prompt_file>")
        sys.exit(1)
    
    prompt_file = sys.argv[1]
    
    try:
        # Read the prompt from the file
        with open(prompt_file, 'r', encoding='utf-8') as f:
            prompt = f.read().strip()
        
        # Initialize Gemini client
        client = GeminiClient()
        
        # Generate response
        response = client.generate_response(prompt)
        
        # Output the response
        print(response)
        
    except Exception as e:
        print(f"Error: {str(e)}")
        sys.exit(1)

if __name__ == "__main__":
    main()
