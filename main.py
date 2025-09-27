from fastapi import FastAPI, HTTPException, status
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
import uuid
from datetime import datetime
from typing import List

from database import db
from models import (
    SessionCreate, SessionUpdate, SessionResponse,
    AnalysisRequest, AppRecommendationRequest, APIResponse,
    HouseholdData, AppRequirements
)
from gemini_client import GeminiClient


app = FastAPI(
    title="AI Budget App Locator API",
    description="Backend API for AI-powered budget analysis and app recommendations",
    version="1.0.0",
    docs_url="/docs",
    redoc_url="/redoc"
)

# Add CORS middleware for frontend integration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Configure this for production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize Gemini client
try:
    gemini_client = GeminiClient()
except Exception as e:
    print(f"Warning: Gemini client initialization failed: {e}")
    gemini_client = None

@app.get("/", response_model=APIResponse)
async def root():
    """Root endpoint - API health check"""
    return APIResponse(
        success=True,
        message="AI Budget App Locator API is running",
        data={"version": "1.0.0", "timestamp": datetime.now().isoformat()}
    )

@app.get("/health", response_model=APIResponse)
async def health_check():
    """Health check endpoint"""
    session_count = db.get_session_count()
    gemini_status = "connected" if gemini_client else "disconnected"

    return APIResponse(
        success=True,
        message="System health check",
        data={
            "database": "connected",
            "gemini_api": gemini_status,
            "sessions_count": session_count,
            "timestamp": datetime.now().isoformat()
        }
    )

# Session Management Endpoints

@app.post("/sessions", response_model=APIResponse)
async def create_session(session_data: SessionCreate):
    """Create a new budget analysis session"""
    try:
        # Convert Pydantic models to dict for storage
        user_data = {
            "household_data": session_data.household_data.dict(),
            "app_requirements": session_data.app_requirements.dict() if session_data.app_requirements else None
        }

        success = db.create_session(session_data.session_id, user_data)

        if success:
            return APIResponse(
                success=True,
                message="Session created successfully",
                data={
                    "session_id": session_data.session_id,
                    "total_expenses": session_data.household_data.total_monthly_expenses,
                    "total_utilities": session_data.household_data.total_utilities
                }
            )
        else:
            raise HTTPException(
                status_code=status.HTTP_409_CONFLICT,
                detail="Session ID already exists"
            )
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to create session: {str(e)}"
        )

@app.get("/sessions/{session_id}", response_model=SessionResponse)
async def get_session(session_id: str):
    """Get a specific session by ID"""
    session = db.get_session(session_id)

    if not session:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Session not found"
        )

    return SessionResponse(**session)

@app.get("/sessions", response_model=List[SessionResponse])
async def get_recent_sessions():
    """Get the 3 most recent sessions"""
    sessions = db.get_recent_sessions(limit=3)
    return [SessionResponse(**session) for session in sessions]

@app.put("/sessions/{session_id}", response_model=APIResponse)
async def update_session(session_id: str, updates: SessionUpdate):
    """Update a session with new data"""
    # Convert Pydantic model to dict, excluding None values
    update_data = {k: v for k, v in updates.dict().items() if v is not None}

    if not update_data:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="No update data provided"
        )

    success = db.update_session(session_id, update_data)

    if success:
        return APIResponse(
            success=True,
            message="Session updated successfully",
            data={"session_id": session_id}
        )
    else:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Session not found or update failed"
        )

@app.delete("/sessions/{session_id}", response_model=APIResponse)
async def delete_session(session_id: str):
    """Delete a specific session"""
    success = db.delete_session(session_id)

    if success:
        return APIResponse(
            success=True,
            message="Session deleted successfully",
            data={"session_id": session_id}
        )
    else:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Session not found"
        )

# AI Analysis Endpoints

@app.post("/analyze-budget", response_model=APIResponse)
async def analyze_budget(request: AnalysisRequest):
    """Analyze budget data using AI"""
    if not gemini_client:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="AI service is not available"
        )

    try:
        # Get session data
        session = db.get_session(request.session_id)
        if not session:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Session not found"
            )

        household_data = session['user_data']['household_data']

        # Create comprehensive budget analysis prompt
        budget_text = f"""
        HOUSEHOLD BUDGET ANALYSIS REQUEST

        Personal Information:
        - Name: {household_data['name']}
        - Location: {household_data['location']}
        - Household Size: {household_data['household_size']} people
        - Housing: {household_data['bedrooms']} bedrooms, {household_data['bathrooms']} bathrooms

        MONTHLY EXPENSES BREAKDOWN:

        Housing Costs:
        - Rent: ${household_data['rent']:.2f}
        - Utilities Total: ${household_data['utilities']['water'] + household_data['utilities']['phone'] + household_data['utilities']['electricity'] + (household_data['utilities'].get('other', 0)):.2f}
          * Water: ${household_data['utilities']['water']:.2f}
          * Phone: ${household_data['utilities']['phone']:.2f}
          * Electricity: ${household_data['utilities']['electricity']:.2f}
          * Other Utilities: ${household_data['utilities'].get('other', 0):.2f}

        Living Expenses:
        - Groceries: ${household_data['groceries']:.2f}
        - Savings Goal: ${household_data['savings']:.2f}

        Debt Information:
        - Total Debt: ${household_data['debt']['total_debt']:.2f}
        - Monthly Debt Payment: ${household_data['debt']['monthly_payment']:.2f}
        - Debt Type: {household_data['debt'].get('debt_type', 'Not specified')}
        - Interest Rate: {household_data['debt'].get('interest_rate', 'Not specified')}%

        Additional Monthly Payments:
        """

        for i, payment in enumerate(household_data['monthly_payments'], 1):
            budget_text += f"  {i}. {payment['name']}: ${payment['amount']:.2f} ({payment.get('category', 'Uncategorized')}) - {'Essential' if payment.get('is_essential', True) else 'Optional'}\n"

        budget_text += f"""

        TOTAL MONTHLY EXPENSES: ${sum([
            household_data['rent'],
            household_data['utilities']['water'] + household_data['utilities']['phone'] + household_data['utilities']['electricity'] + household_data['utilities'].get('other', 0),
            household_data['groceries'],
            household_data['debt']['monthly_payment'],
            sum(payment['amount'] for payment in household_data['monthly_payments'])
        ]):.2f}

        Please provide a comprehensive analysis including:
        1. Cost of living assessment for {household_data['location']}
        2. Housing cost analysis (rent vs. local averages)
        3. Utility cost breakdown and efficiency recommendations
        4. Grocery budget analysis for {household_data['household_size']} people
        5. Debt management strategy
        6. Savings optimization recommendations
        7. Monthly payment prioritization
        8. Overall budget health score and recommendations
        """

        # Get AI analysis
        analysis_text = gemini_client.analyze_budget_data(budget_text)

        # Parse and structure the analysis
        analysis_data = {
            "raw_analysis": analysis_text,
            "household_data": household_data,
            "analysis_options": {
                "include_location_analysis": request.include_location_analysis,
                "include_household_analysis": request.include_household_analysis
            },
            "analyzed_at": datetime.now().isoformat(),
            "total_expenses": sum([
                household_data['rent'],
                household_data['utilities']['water'] + household_data['utilities']['phone'] + household_data['utilities']['electricity'] + household_data['utilities'].get('other', 0),
                household_data['groceries'],
                household_data['debt']['monthly_payment'],
                sum(payment['amount'] for payment in household_data['monthly_payments'])
            ])
        }

        # Update session with analysis
        db.update_session(request.session_id, {"budget_analysis": analysis_data})

        return APIResponse(
            success=True,
            message="Budget analysis completed",
            data={
                "session_id": request.session_id,
                "analysis": analysis_data
            }
        )

    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Analysis failed: {str(e)}"
        )

@app.post("/recommend-apps", response_model=APIResponse)
async def recommend_apps(request: AppRecommendationRequest):
    """Get AI-powered budget app recommendations"""
    if not gemini_client:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="AI service is not available"
        )

    try:
        # Get session data
        session = db.get_session(request.session_id)
        if not session:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Session not found"
            )

        household_data = session['user_data']['household_data']
        app_requirements = session['user_data'].get('app_requirements', {})

        # Create comprehensive app recommendation prompt
        requirements_text = f"""
        BUDGET APP RECOMMENDATION REQUEST

        User Profile:
        - Name: {household_data['name']}
        - Location: {household_data['location']}
        - Household Size: {household_data['household_size']} people
        - Housing: {household_data['bedrooms']} bedrooms, {household_data['bathrooms']} bathrooms

        Current Budget Complexity:
        - Monthly Rent: ${household_data['rent']:.2f}
        - Utilities: ${household_data['utilities']['water'] + household_data['utilities']['phone'] + household_data['utilities']['electricity'] + household_data['utilities'].get('other', 0):.2f} (water, phone, electricity)
        - Groceries: ${household_data['groceries']:.2f}
        - Savings Goal: ${household_data['savings']:.2f}
        - Debt: ${household_data['debt']['total_debt']:.2f} (${household_data['debt']['monthly_payment']:.2f}/month)
        - Additional Payments: {len(household_data['monthly_payments'])} payments totaling ${sum(payment['amount'] for payment in household_data['monthly_payments']):.2f}

        App Requirements:
        - Features: {', '.join(app_requirements.get('features', ['expense_tracking', 'budget_planning']))}
        - Budget Range: {app_requirements.get('budget_range', 'Any')}
        - Platform: {app_requirements.get('platform', 'Any')}
        - Experience Level: {app_requirements.get('experience_level', 'Any')}
        """

        if request.prioritize_features:
            requirements_text += f"\n- Prioritized Features: {', '.join(request.prioritize_features)}"

        requirements_text += f"""

        Please recommend budget apps that would work well for this user's situation, considering:
        1. Their location ({household_data['location']}) and cost of living
        2. Household size ({household_data['household_size']} people)
        3. Budget complexity (multiple utilities, debt, various payments)
        4. Specific features they need
        5. Their experience level
        6. Budget constraints

        For each recommendation, provide:
        - App name and brief description
        - Key features that match their needs
        - Pricing information
        - Pros and cons
        - Why it's suitable for their situation
        - Platform availability
        """

        # Get AI recommendations
        recommendations_text = gemini_client.find_budget_apps(requirements_text)

        # Parse and structure the recommendations
        recommendations_data = {
            "raw_recommendations": recommendations_text,
            "household_context": {
                "location": household_data['location'],
                "household_size": household_data['household_size'],
                "budget_complexity": len(household_data['monthly_payments']) + 4,  # rent, utilities, groceries, debt
                "total_monthly_expenses": sum([
                    household_data['rent'],
                    household_data['utilities']['water'] + household_data['utilities']['phone'] + household_data['utilities']['electricity'] + household_data['utilities'].get('other', 0),
                    household_data['groceries'],
                    household_data['debt']['monthly_payment'],
                    sum(payment['amount'] for payment in household_data['monthly_payments'])
                ])
            },
            "requirements": app_requirements,
            "prioritized_features": request.prioritize_features,
            "recommended_at": datetime.now().isoformat()
        }

        # Update session with recommendations
        db.update_session(request.session_id, {"app_recommendations": recommendations_data})

        return APIResponse(
            success=True,
            message="App recommendations generated",
            data={
                "session_id": request.session_id,
                "recommendations": recommendations_data
            }
        )

    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Recommendation generation failed: {str(e)}"
        )

# Utility Endpoints

@app.get("/generate-session-id", response_model=APIResponse)
async def generate_session_id():
    """Generate a unique session ID for frontend use"""
    session_id = f"session_{uuid.uuid4().hex[:12]}"
    return APIResponse(
        success=True,
        message="Session ID generated",
        data={"session_id": session_id}
    )

@app.get("/stats", response_model=APIResponse)
async def get_stats():
    """Get API usage statistics"""
    session_count = db.get_session_count()
    recent_sessions = db.get_recent_sessions(limit=3)

    return APIResponse(
        success=True,
        message="API statistics",
        data={
            "total_sessions": session_count,
            "recent_sessions_count": len(recent_sessions),
            "ai_service_status": "connected" if gemini_client else "disconnected",
            "timestamp": datetime.now().isoformat()
        }
    )

# Error handlers
@app.exception_handler(404)
async def not_found_handler(request, exc):
    return JSONResponse(
        status_code=404,
        content={"success": False, "message": "Endpoint not found", "data": None}
    )

@app.exception_handler(500)
async def internal_error_handler(request, exc):
    return JSONResponse(
        status_code=500,
        content={"success": False, "message": "Internal server error", "data": None}
    )

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
