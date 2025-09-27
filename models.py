from pydantic import BaseModel, Field, validator
from typing import Optional, Dict, Any, List
from datetime import datetime

class UtilitiesData(BaseModel):
    """Model for utility breakdown"""
    water: float = Field(0.0, description="Monthly water bill")
    phone: float = Field(0.0, description="Monthly phone bill")
    electricity: float = Field(0.0, description="Monthly electricity bill")
    other: Optional[float] = Field(0.0, description="Other utilities")
    
    @property
    def total(self) -> float:
        """Calculate total utilities"""
        return self.water + self.phone + self.electricity + (self.other or 0.0)

class DebtData(BaseModel):
    """Model for debt information"""
    total_debt: float = Field(0.0, description="Total debt amount")
    monthly_payment: float = Field(0.0, description="Monthly debt payment")
    debt_type: Optional[str] = Field(None, description="Type of debt (student loan, credit card, etc.)")
    interest_rate: Optional[float] = Field(None, description="Interest rate percentage")

class MonthlyPayment(BaseModel):
    """Model for editable monthly payments"""
    name: str = Field(..., description="Payment name/description")
    amount: float = Field(..., description="Monthly payment amount")
    category: Optional[str] = Field(None, description="Payment category")
    is_essential: bool = Field(True, description="Whether this is an essential payment")

class HouseholdData(BaseModel):
    """Model for comprehensive household budget data"""
    # Personal Information
    name: str = Field(..., description="User's name")
    age: int = Field(..., ge=18, le=120, description="User's age")
    location: str = Field(..., description="City, State/Country")
    
    # Household Details
    household_size: int = Field(..., ge=1, description="Number of people in household")
    bedrooms: int = Field(..., ge=0, description="Number of bedrooms")
    bathrooms: float = Field(..., ge=0, description="Number of bathrooms")
    
    # Housing Costs
    rent: float = Field(..., ge=0, description="Monthly rent")
    utilities: UtilitiesData = Field(..., description="Utility breakdown")
    
    # Living Expenses
    groceries: float = Field(..., ge=0, description="Monthly grocery budget")
    savings: float = Field(..., ge=0, description="Monthly savings goal")
    
    # Debt Information
    debt: DebtData = Field(..., description="Debt information")
    
    # Additional Monthly Payments (4 editable slots)
    monthly_payments: List[MonthlyPayment] = Field(default_factory=list, max_items=4, description="Up to 4 additional monthly payments")
    
    # Calculated Properties
    @property
    def total_monthly_expenses(self) -> float:
        """Calculate total monthly expenses"""
        total = self.rent + self.utilities.total + self.groceries + self.debt.monthly_payment
        total += sum(payment.amount for payment in self.monthly_payments)
        return total
    
    @property
    def total_utilities(self) -> float:
        """Get total utilities"""
        return self.utilities.total
    
    @validator('monthly_payments')
    def validate_monthly_payments(cls, v):
        """Ensure we don't exceed 4 payments"""
        if len(v) > 4:
            raise ValueError('Maximum 4 monthly payments allowed')
        return v
    
    class Config:
        json_schema_extra = {
            "example": {
                "name": "John Doe",
                "age": 28,
                "location": "Austin, TX",
                "household_size": 2,
                "bedrooms": 2,
                "bathrooms": 1.5,
                "rent": 1500.0,
                "utilities": {
                    "water": 80.0,
                    "phone": 120.0,
                    "electricity": 150.0,
                    "other": 30.0
                },
                "groceries": 600.0,
                "savings": 500.0,
                "debt": {
                    "total_debt": 25000.0,
                    "monthly_payment": 300.0,
                    "debt_type": "student_loan",
                    "interest_rate": 4.5
                },
                "monthly_payments": [
                    {"name": "Car Payment", "amount": 350.0, "category": "transportation", "is_essential": True},
                    {"name": "Gym Membership", "amount": 50.0, "category": "health", "is_essential": False},
                    {"name": "Streaming Services", "amount": 25.0, "category": "entertainment", "is_essential": False}
                ]
            }
        }

class AppRequirements(BaseModel):
    """Model for budget app requirements"""
    features: List[str] = Field(..., description="Required features")
    budget_range: Optional[str] = Field(None, description="Budget range (free, paid, premium)")
    platform: Optional[str] = Field(None, description="Platform preference (mobile, web, desktop)")
    experience_level: Optional[str] = Field(None, description="User experience level (beginner, intermediate, advanced)")
    
    class Config:
        json_schema_extra = {
            "example": {
                "features": ["expense_tracking", "budget_planning", "savings_goals"],
                "budget_range": "free",
                "platform": "mobile",
                "experience_level": "beginner"
            }
        }

class SessionCreate(BaseModel):
    """Model for creating a new session"""
    session_id: str = Field(..., description="Unique session identifier")
    household_data: HouseholdData = Field(..., description="Comprehensive household budget data")
    app_requirements: Optional[AppRequirements] = Field(None, description="Budget app requirements")
    
    class Config:
        json_schema_extra = {
            "example": {
                "session_id": "user_123_session_001",
                "household_data": {
                    "name": "John Doe",
                    "location": "Austin, TX",
                    "household_size": 2,
                    "bedrooms": 2,
                    "bathrooms": 1.5,
                    "rent": 1500.0,
                    "utilities": {"water": 80.0, "phone": 120.0, "electricity": 150.0},
                    "groceries": 600.0,
                    "savings": 500.0,
                    "debt": {"total_debt": 25000.0, "monthly_payment": 300.0},
                    "monthly_payments": [{"name": "Car Payment", "amount": 350.0}]
                },
                "app_requirements": {
                    "features": ["expense_tracking"],
                    "budget_range": "free"
                }
            }
        }

class SessionUpdate(BaseModel):
    """Model for updating a session"""
    budget_analysis: Optional[Dict[str, Any]] = Field(None, description="AI budget analysis results")
    app_recommendations: Optional[Dict[str, Any]] = Field(None, description="AI app recommendations")
    user_data: Optional[Dict[str, Any]] = Field(None, description="Updated user data")
    
    class Config:
        json_schema_extra = {
            "example": {
                "budget_analysis": {
                    "insights": ["Good savings rate", "High rent costs"],
                    "recommendations": ["Reduce entertainment spending"]
                },
                "app_recommendations": {
                    "top_apps": ["Mint", "YNAB"],
                    "reasoning": "Best for beginners"
                }
            }
        }

class SessionResponse(BaseModel):
    """Model for session response"""
    id: int
    session_id: str
    user_data: Dict[str, Any]
    budget_analysis: Optional[Dict[str, Any]] = None
    app_recommendations: Optional[Dict[str, Any]] = None
    created_at: str
    updated_at: str
    
    class Config:
        json_schema_extra = {
            "example": {
                "id": 1,
                "session_id": "user_123_session_001",
                "user_data": {
                    "budget_data": {"income": 5000.0},
                    "app_requirements": {"features": ["tracking"]}
                },
                "budget_analysis": {
                    "insights": ["Good savings rate"]
                },
                "app_recommendations": {
                    "top_apps": ["Mint"]
                },
                "created_at": "2025-01-27T10:30:00",
                "updated_at": "2025-01-27T10:35:00"
            }
        }

class AnalysisRequest(BaseModel):
    """Model for budget analysis request"""
    session_id: str = Field(..., description="Session ID to associate with analysis")
    include_location_analysis: bool = Field(True, description="Include location-based cost analysis")
    include_household_analysis: bool = Field(True, description="Include household size analysis")
    
class AppRecommendationRequest(BaseModel):
    """Model for app recommendation request"""
    session_id: str = Field(..., description="Session ID to associate with recommendations")
    prioritize_features: Optional[List[str]] = Field(None, description="Prioritize specific features")

class APIResponse(BaseModel):
    """Standard API response model"""
    success: bool
    message: str
    data: Optional[Dict[str, Any]] = None
    
    class Config:
        json_schema_extra = {
            "example": {
                "success": True,
                "message": "Operation completed successfully",
                "data": {"session_id": "user_123_session_001"}
            }
        }