#!/usr/bin/env python3
"""
Simple GUI for testing the AI Budget Analyzer
Allows input of household data, viewing database, and testing AI budget analysis
"""

import tkinter as tk
from tkinter import ttk, messagebox, scrolledtext
import json
from datetime import datetime
import uuid
from database import db
from models import HouseholdData, UtilitiesData, DebtData, MonthlyPayment

class BudgetAppGUI:
    def __init__(self, root):
        self.root = root
        self.root.title("AI Budget Analyzer - Test GUI")
        self.root.geometry("1200x800")
        
        # Create notebook for tabs
        self.notebook = ttk.Notebook(root)
        self.notebook.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
        
        # Create tabs
        self.create_input_tab()
        self.create_database_tab()
        self.create_ai_test_tab()
        self.create_profile_tab()
        
        # Current session data
        self.current_session_id = None
        self.current_household_data = None
        
    def create_input_tab(self):
        """Create the data input tab"""
        input_frame = ttk.Frame(self.notebook)
        self.notebook.add(input_frame, text="Input Data")
        
        # Create scrollable frame
        canvas = tk.Canvas(input_frame)
        scrollbar = ttk.Scrollbar(input_frame, orient="vertical", command=canvas.yview)
        scrollable_frame = ttk.Frame(canvas)
        
        scrollable_frame.bind(
            "<Configure>",
            lambda e: canvas.configure(scrollregion=canvas.bbox("all"))
        )
        
        canvas.create_window((0, 0), window=scrollable_frame, anchor="nw")
        canvas.configure(yscrollcommand=scrollbar.set)
        
        # Personal Information
        personal_frame = ttk.LabelFrame(scrollable_frame, text="Personal Information", padding=10)
        personal_frame.grid(row=0, column=0, columnspan=2, sticky="ew", pady=5)
        
        ttk.Label(personal_frame, text="Name:").grid(row=0, column=0, sticky="w")
        self.name_var = tk.StringVar(value="John Doe")
        ttk.Entry(personal_frame, textvariable=self.name_var, width=30).grid(row=0, column=1, padx=5)
        
        ttk.Label(personal_frame, text="Age:").grid(row=0, column=2, sticky="w", padx=(20,0))
        self.age_var = tk.StringVar(value="28")
        ttk.Entry(personal_frame, textvariable=self.age_var, width=10).grid(row=0, column=3, padx=5)
        
        ttk.Label(personal_frame, text="Location:").grid(row=1, column=0, sticky="w")
        self.location_var = tk.StringVar(value="Austin, TX")
        ttk.Entry(personal_frame, textvariable=self.location_var, width=30).grid(row=1, column=1, padx=5)
        
        # Household Details
        household_frame = ttk.LabelFrame(scrollable_frame, text="Household Details", padding=10)
        household_frame.grid(row=1, column=0, columnspan=2, sticky="ew", pady=5)
        
        ttk.Label(household_frame, text="Household Size:").grid(row=0, column=0, sticky="w")
        self.household_size_var = tk.StringVar(value="2")
        ttk.Entry(household_frame, textvariable=self.household_size_var, width=10).grid(row=0, column=1, padx=5)
        
        ttk.Label(household_frame, text="Bedrooms:").grid(row=0, column=2, sticky="w", padx=(20,0))
        self.bedrooms_var = tk.StringVar(value="2")
        ttk.Entry(household_frame, textvariable=self.bedrooms_var, width=10).grid(row=0, column=3, padx=5)
        
        ttk.Label(household_frame, text="Bathrooms:").grid(row=1, column=0, sticky="w")
        self.bathrooms_var = tk.StringVar(value="1.5")
        ttk.Entry(household_frame, textvariable=self.bathrooms_var, width=10).grid(row=1, column=1, padx=5)
        
        # Housing Costs
        housing_frame = ttk.LabelFrame(scrollable_frame, text="Housing Costs", padding=10)
        housing_frame.grid(row=2, column=0, columnspan=2, sticky="ew", pady=5)
        
        ttk.Label(housing_frame, text="Monthly Rent:").grid(row=0, column=0, sticky="w")
        self.rent_var = tk.StringVar(value="1500.0")
        ttk.Entry(housing_frame, textvariable=self.rent_var, width=15).grid(row=0, column=1, padx=5)
        
        ttk.Label(housing_frame, text="Water Bill:").grid(row=0, column=2, sticky="w", padx=(20,0))
        self.water_var = tk.StringVar(value="80.0")
        ttk.Entry(housing_frame, textvariable=self.water_var, width=15).grid(row=0, column=3, padx=5)
        
        ttk.Label(housing_frame, text="Phone Bill:").grid(row=1, column=0, sticky="w")
        self.phone_var = tk.StringVar(value="120.0")
        ttk.Entry(housing_frame, textvariable=self.phone_var, width=15).grid(row=1, column=1, padx=5)
        
        ttk.Label(housing_frame, text="Electricity:").grid(row=1, column=2, sticky="w", padx=(20,0))
        self.electricity_var = tk.StringVar(value="150.0")
        ttk.Entry(housing_frame, textvariable=self.electricity_var, width=15).grid(row=1, column=3, padx=5)
        
        ttk.Label(housing_frame, text="Other Utilities:").grid(row=2, column=0, sticky="w")
        self.other_utilities_var = tk.StringVar(value="30.0")
        ttk.Entry(housing_frame, textvariable=self.other_utilities_var, width=15).grid(row=2, column=1, padx=5)
        
        # Living Expenses
        living_frame = ttk.LabelFrame(scrollable_frame, text="Living Expenses", padding=10)
        living_frame.grid(row=3, column=0, columnspan=2, sticky="ew", pady=5)
        
        ttk.Label(living_frame, text="Monthly Income:").grid(row=0, column=0, sticky="w")
        self.income_var = tk.StringVar(value="5000.0")
        ttk.Entry(living_frame, textvariable=self.income_var, width=15).grid(row=0, column=1, padx=5)
        
        ttk.Label(living_frame, text="Groceries:").grid(row=0, column=2, sticky="w", padx=(20,0))
        self.groceries_var = tk.StringVar(value="600.0")
        ttk.Entry(living_frame, textvariable=self.groceries_var, width=15).grid(row=0, column=3, padx=5)
        
        ttk.Label(living_frame, text="Savings Goal:").grid(row=1, column=0, sticky="w")
        self.savings_var = tk.StringVar(value="500.0")
        ttk.Entry(living_frame, textvariable=self.savings_var, width=15).grid(row=1, column=1, padx=5)
        
        # Debt Information
        debt_frame = ttk.LabelFrame(scrollable_frame, text="Debt Information", padding=10)
        debt_frame.grid(row=4, column=0, columnspan=2, sticky="ew", pady=5)
        
        ttk.Label(debt_frame, text="Total Debt:").grid(row=0, column=0, sticky="w")
        self.total_debt_var = tk.StringVar(value="25000.0")
        ttk.Entry(debt_frame, textvariable=self.total_debt_var, width=15).grid(row=0, column=1, padx=5)
        
        ttk.Label(debt_frame, text="Monthly Payment:").grid(row=0, column=2, sticky="w", padx=(20,0))
        self.debt_payment_var = tk.StringVar(value="300.0")
        ttk.Entry(debt_frame, textvariable=self.debt_payment_var, width=15).grid(row=0, column=3, padx=5)
        
        ttk.Label(debt_frame, text="Debt Type:").grid(row=1, column=0, sticky="w")
        self.debt_type_var = tk.StringVar(value="student_loan")
        debt_type_combo = ttk.Combobox(debt_frame, textvariable=self.debt_type_var, width=15)
        debt_type_combo['values'] = ('student_loan', 'credit_card', 'mortgage', 'car_loan', 'personal_loan', 'other')
        debt_type_combo.grid(row=1, column=1, padx=5)
        
        ttk.Label(debt_frame, text="Interest Rate (%):").grid(row=1, column=2, sticky="w", padx=(20,0))
        self.interest_rate_var = tk.StringVar(value="4.5")
        ttk.Entry(debt_frame, textvariable=self.interest_rate_var, width=15).grid(row=1, column=3, padx=5)
        
        # Monthly Payments
        payments_frame = ttk.LabelFrame(scrollable_frame, text="Additional Monthly Payments", padding=10)
        payments_frame.grid(row=5, column=0, columnspan=2, sticky="ew", pady=5)
        
        # Payment 1
        ttk.Label(payments_frame, text="Payment 1:").grid(row=0, column=0, sticky="w")
        self.payment1_name_var = tk.StringVar(value="Car Payment")
        ttk.Entry(payments_frame, textvariable=self.payment1_name_var, width=20).grid(row=0, column=1, padx=5)
        self.payment1_amount_var = tk.StringVar(value="350.0")
        ttk.Entry(payments_frame, textvariable=self.payment1_amount_var, width=10).grid(row=0, column=2, padx=5)
        
        # Payment 2
        ttk.Label(payments_frame, text="Payment 2:").grid(row=1, column=0, sticky="w")
        self.payment2_name_var = tk.StringVar(value="Gym Membership")
        ttk.Entry(payments_frame, textvariable=self.payment2_name_var, width=20).grid(row=1, column=1, padx=5)
        self.payment2_amount_var = tk.StringVar(value="50.0")
        ttk.Entry(payments_frame, textvariable=self.payment2_amount_var, width=10).grid(row=1, column=2, padx=5)
        
        # Payment 3
        ttk.Label(payments_frame, text="Payment 3:").grid(row=2, column=0, sticky="w")
        self.payment3_name_var = tk.StringVar(value="Streaming Services")
        ttk.Entry(payments_frame, textvariable=self.payment3_name_var, width=20).grid(row=2, column=1, padx=5)
        self.payment3_amount_var = tk.StringVar(value="25.0")
        ttk.Entry(payments_frame, textvariable=self.payment3_amount_var, width=10).grid(row=2, column=2, padx=5)
        
        # Payment 4
        ttk.Label(payments_frame, text="Payment 4:").grid(row=3, column=0, sticky="w")
        self.payment4_name_var = tk.StringVar(value="")
        ttk.Entry(payments_frame, textvariable=self.payment4_name_var, width=20).grid(row=3, column=1, padx=5)
        self.payment4_amount_var = tk.StringVar(value="0.0")
        ttk.Entry(payments_frame, textvariable=self.payment4_amount_var, width=10).grid(row=3, column=2, padx=5)
        
        # Status indicator
        status_frame = ttk.Frame(scrollable_frame)
        status_frame.grid(row=6, column=0, columnspan=2, pady=5)
        
        self.status_label = ttk.Label(status_frame, text="No data loaded", foreground="gray")
        self.status_label.pack()
        
        # Buttons
        button_frame = ttk.Frame(scrollable_frame)
        button_frame.grid(row=7, column=0, columnspan=2, pady=20)
        
        ttk.Button(button_frame, text="Save to Database", command=self.save_data).pack(side=tk.LEFT, padx=5)
        ttk.Button(button_frame, text="Calculate Totals", command=self.calculate_totals).pack(side=tk.LEFT, padx=5)
        ttk.Button(button_frame, text="Load from Database", command=self.load_from_database).pack(side=tk.LEFT, padx=5)
        ttk.Button(button_frame, text="Clear Form", command=self.clear_form).pack(side=tk.LEFT, padx=5)
        
        # Totals Display
        totals_frame = ttk.LabelFrame(scrollable_frame, text="Calculated Totals", padding=10)
        totals_frame.grid(row=8, column=0, columnspan=2, sticky="ew", pady=5)
        
        self.totals_text = tk.Text(totals_frame, height=8, width=60)
        self.totals_text.pack()
        
        # Pack canvas and scrollbar
        canvas.pack(side="left", fill="both", expand=True)
        scrollbar.pack(side="right", fill="y")
        
    def create_database_tab(self):
        """Create the database viewing tab"""
        db_frame = ttk.Frame(self.notebook)
        self.notebook.add(db_frame, text="Database View")
        
        # Controls
        control_frame = ttk.Frame(db_frame)
        control_frame.pack(fill=tk.X, padx=10, pady=5)
        
        ttk.Button(control_frame, text="Refresh Database", command=self.refresh_database).pack(side=tk.LEFT, padx=5)
        ttk.Button(control_frame, text="Clear Database", command=self.clear_database).pack(side=tk.LEFT, padx=5)
        
        # Database content
        self.db_text = scrolledtext.ScrolledText(db_frame, height=30, width=100)
        self.db_text.pack(fill=tk.BOTH, expand=True, padx=10, pady=5)
        
        # Load initial data
        self.refresh_database()
        
    def create_ai_test_tab(self):
        """Create the AI testing tab"""
        ai_frame = ttk.Frame(self.notebook)
        self.notebook.add(ai_frame, text="AI Test")
        
        # Create scrollable frame
        canvas = tk.Canvas(ai_frame)
        scrollbar = ttk.Scrollbar(ai_frame, orient="vertical", command=canvas.yview)
        scrollable_frame = ttk.Frame(canvas)
        
        scrollable_frame.bind(
            "<Configure>",
            lambda e: canvas.configure(scrollregion=canvas.bbox("all"))
        )
        
        canvas.create_window((0, 0), window=scrollable_frame, anchor="nw")
        canvas.configure(yscrollcommand=scrollbar.set)
        
        # Location Input Section
        location_frame = ttk.LabelFrame(scrollable_frame, text="Location-Based Cost Analysis", padding=10)
        location_frame.grid(row=0, column=0, columnspan=2, sticky="ew", pady=5)
        
        ttk.Label(location_frame, text="Location:").grid(row=0, column=0, sticky="w")
        self.ai_location_var = tk.StringVar(value="Austin, TX")
        ttk.Entry(location_frame, textvariable=self.ai_location_var, width=30).grid(row=0, column=1, padx=5)
        
        ttk.Label(location_frame, text="Household Size:").grid(row=0, column=2, sticky="w", padx=(20,0))
        self.ai_household_size_var = tk.StringVar(value="2")
        ttk.Entry(location_frame, textvariable=self.ai_household_size_var, width=10).grid(row=0, column=3, padx=5)
        
        # Generation mode selection
        mode_frame = ttk.Frame(location_frame)
        mode_frame.grid(row=1, column=0, columnspan=4, pady=5)
        
        ttk.Label(mode_frame, text="Generation Mode:").pack(side=tk.LEFT)
        self.generation_mode = tk.StringVar(value="basic")
        ttk.Radiobutton(mode_frame, text="Basic (Quick)", variable=self.generation_mode, value="basic").pack(side=tk.LEFT, padx=5)
        ttk.Radiobutton(mode_frame, text="Advanced (Detailed)", variable=self.generation_mode, value="advanced").pack(side=tk.LEFT, padx=5)
        
        # Main action buttons
        button_frame1 = ttk.Frame(location_frame)
        button_frame1.grid(row=2, column=0, columnspan=4, pady=10)
        
        ttk.Button(button_frame1, text="Get Average Living Costs", command=self.get_average_costs).pack(side=tk.LEFT, padx=5)
        ttk.Button(button_frame1, text="Apply to Form", command=self.apply_costs_to_form).pack(side=tk.LEFT, padx=5)
        
        # Additional buttons for direct save
        button_frame2 = ttk.Frame(location_frame)
        button_frame2.grid(row=3, column=0, columnspan=4, pady=5)
        
        ttk.Button(button_frame2, text="Create & Save Complete Profile", command=self.create_and_save_profile).pack(side=tk.LEFT, padx=5)
        ttk.Button(button_frame2, text="Preview Data Structure", command=self.preview_data_structure).pack(side=tk.LEFT, padx=5)
        
        # Cost Analysis Results
        cost_frame = ttk.LabelFrame(scrollable_frame, text="Average Living Costs", padding=10)
        cost_frame.grid(row=1, column=0, columnspan=2, sticky="ew", pady=5)
        
        self.cost_text = tk.Text(cost_frame, height=8, width=80)
        self.cost_text.pack()
        
        # Controls
        control_frame = ttk.Frame(scrollable_frame)
        control_frame.grid(row=2, column=0, columnspan=2, pady=10)
        
        ttk.Button(control_frame, text="Test Budget Analysis", command=self.test_budget_analysis).pack(side=tk.LEFT, padx=5)
        ttk.Button(control_frame, text="Save Current Data", command=self.save_from_ai_tab).pack(side=tk.LEFT, padx=5)
        ttk.Button(control_frame, text="Clear Results", command=self.clear_ai_results).pack(side=tk.LEFT, padx=5)
        
        # AI Results
        results_frame = ttk.LabelFrame(scrollable_frame, text="AI Analysis Results", padding=10)
        results_frame.grid(row=3, column=0, columnspan=2, sticky="ew", pady=5)
        
        self.ai_text = scrolledtext.ScrolledText(results_frame, height=15, width=100)
        self.ai_text.pack(fill=tk.BOTH, expand=True)
        
        # Pack canvas and scrollbar
        canvas.pack(side="left", fill="both", expand=True)
        scrollbar.pack(side="right", fill="y")
        
        # Store AI cost data
        self.ai_cost_data = None
        
        # Store current profile
        self.current_profile = None
        
        # Location-based cost baselines for consistency
        self.location_baselines = {
            'austin, tx': {'rent_multiplier': 1.0, 'income_multiplier': 1.0, 'cost_index': 1.0},
            'san francisco, ca': {'rent_multiplier': 2.5, 'income_multiplier': 1.8, 'cost_index': 2.2},
            'new york, ny': {'rent_multiplier': 2.2, 'income_multiplier': 1.6, 'cost_index': 2.0},
            'los angeles, ca': {'rent_multiplier': 2.0, 'income_multiplier': 1.4, 'cost_index': 1.8},
            'chicago, il': {'rent_multiplier': 1.2, 'income_multiplier': 1.1, 'cost_index': 1.1},
            'houston, tx': {'rent_multiplier': 0.9, 'income_multiplier': 0.95, 'cost_index': 0.9},
            'phoenix, az': {'rent_multiplier': 1.1, 'income_multiplier': 1.0, 'cost_index': 1.0},
            'philadelphia, pa': {'rent_multiplier': 1.3, 'income_multiplier': 1.1, 'cost_index': 1.2},
            'san antonio, tx': {'rent_multiplier': 0.8, 'income_multiplier': 0.9, 'cost_index': 0.85},
            'san diego, ca': {'rent_multiplier': 1.8, 'income_multiplier': 1.3, 'cost_index': 1.6},
            'dallas, tx': {'rent_multiplier': 1.0, 'income_multiplier': 1.0, 'cost_index': 1.0},
            'san jose, ca': {'rent_multiplier': 2.3, 'income_multiplier': 1.7, 'cost_index': 2.1},
            'miami, fl': {'rent_multiplier': 1.4, 'income_multiplier': 1.1, 'cost_index': 1.3},
            'atlanta, ga': {'rent_multiplier': 1.1, 'income_multiplier': 1.0, 'cost_index': 1.0},
            'seattle, wa': {'rent_multiplier': 1.6, 'income_multiplier': 1.3, 'cost_index': 1.5}
        }
        
    def get_household_data(self):
        """Get household data from form inputs"""
        try:
            # Get monthly payments
            monthly_payments = []
            payments = [
                (self.payment1_name_var.get(), self.payment1_amount_var.get()),
                (self.payment2_name_var.get(), self.payment2_amount_var.get()),
                (self.payment3_name_var.get(), self.payment3_amount_var.get()),
                (self.payment4_name_var.get(), self.payment4_amount_var.get())
            ]
            
            for name, amount in payments:
                if name.strip() and float(amount) > 0:
                    monthly_payments.append(MonthlyPayment(
                        name=name,
                        amount=float(amount),
                        category="other",
                        is_essential=True
                    ))
            
            # Create household data
            household_data = HouseholdData(
                name=self.name_var.get(),
                age=int(self.age_var.get()),
                location=self.location_var.get(),
                household_size=int(self.household_size_var.get()),
                bedrooms=int(self.bedrooms_var.get()),
                bathrooms=float(self.bathrooms_var.get()),
                rent=float(self.rent_var.get()),
                utilities=UtilitiesData(
                    water=float(self.water_var.get()),
                    phone=float(self.phone_var.get()),
                    electricity=float(self.electricity_var.get()),
                    other=float(self.other_utilities_var.get())
                ),
                monthly_income=float(self.income_var.get()),
                groceries=float(self.groceries_var.get()),
                savings=float(self.savings_var.get()),
                debt=DebtData(
                    total_debt=float(self.total_debt_var.get()),
                    monthly_payment=float(self.debt_payment_var.get()),
                    debt_type=self.debt_type_var.get(),
                    interest_rate=float(self.interest_rate_var.get())
                ),
                monthly_payments=monthly_payments
            )
            
            return household_data
            
        except ValueError as e:
            messagebox.showerror("Input Error", f"Please check your input values: {e}")
            return None
    
    def calculate_totals(self):
        """Calculate and display totals"""
        household_data = self.get_household_data()
        if not household_data:
            return
            
        totals = f"""
MONTHLY TOTALS:
- Monthly Income: ${household_data.monthly_income:,.2f}
- Monthly Expenses: ${household_data.total_monthly_expenses:,.2f}
- Monthly Savings Goal: ${household_data.savings:,.2f}
- Monthly Remaining: ${household_data.monthly_savings_potential:,.2f}

YEARLY TOTALS:
- Yearly Income: ${household_data.total_yearly_income:,.2f}
- Yearly Expenses: ${household_data.total_yearly_expenses:,.2f}
- Yearly Savings Goal: ${household_data.savings * 12:,.2f}
- Yearly Remaining: ${household_data.yearly_savings_potential:,.2f}

FINANCIAL HEALTH METRICS:
- Savings Rate: {household_data.savings_rate:.1f}%
- Debt-to-Income Ratio: {household_data.debt_to_income_ratio:.1f}%
- Total Utilities: ${household_data.total_utilities:,.2f}

BREAKDOWN:
- Rent: ${household_data.rent:,.2f}
- Utilities: ${household_data.total_utilities:,.2f}
- Groceries: ${household_data.groceries:,.2f}
- Debt Payment: ${household_data.debt.monthly_payment:,.2f}
- Additional Payments: ${sum(p.amount for p in household_data.monthly_payments):,.2f}
"""
        
        self.totals_text.delete(1.0, tk.END)
        self.totals_text.insert(1.0, totals)
    
    def save_data(self):
        """Save data to database"""
        household_data = self.get_household_data()
        if not household_data:
            return
            
        try:
            # Create user data (this matches the database structure)
            user_data = {
                "household_data": household_data.model_dump()
            }
            
            if self.current_session_id:
                # Update existing session
                success = db.update_session(self.current_session_id, user_data)
                if success:
                    self.current_household_data = household_data
                    self.status_label.config(text=f"Updated: {self.current_session_id} (Auto-cleanup: Max 3 sessions)", foreground="blue")
                    messagebox.showinfo("Success", f"Data updated successfully!\nSession ID: {self.current_session_id}\n\nNote: Database automatically keeps only the 3 most recent sessions.")
                    # Small delay to ensure database operations complete
                    self.root.after(100, self.refresh_database)
                else:
                    messagebox.showerror("Error", "Failed to update data. Session might not exist.")
            else:
                # Create new session
                session_id = f"session_{uuid.uuid4().hex[:12]}"
                success = db.create_session(session_id, user_data)
                
                if success:
                    self.current_session_id = session_id
                    self.current_household_data = household_data
                    self.status_label.config(text=f"Saved: {session_id} (Auto-cleanup: Max 3 sessions)", foreground="green")
                    messagebox.showinfo("Success", f"Data saved successfully!\nSession ID: {session_id}\n\nNote: Database automatically keeps only the 3 most recent sessions.")
                    # Small delay to ensure database operations complete
                    self.root.after(100, self.refresh_database)
                else:
                    messagebox.showerror("Error", "Failed to save data. Session ID might already exist.")
                
        except Exception as e:
            messagebox.showerror("Error", f"Failed to save data: {str(e)}")
    
    def load_from_database(self):
        """Load data from database into form"""
        try:
            # Get recent sessions
            sessions = db.get_recent_sessions(limit=20)
            
            if not sessions:
                messagebox.showinfo("Info", "No sessions found in database.")
                return
            
            # Create session selection dialog
            session_dialog = tk.Toplevel(self.root)
            session_dialog.title("Select Session to Load")
            session_dialog.geometry("600x400")
            session_dialog.transient(self.root)
            session_dialog.grab_set()
            
            # Create listbox with sessions
            listbox_frame = ttk.Frame(session_dialog)
            listbox_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
            
            ttk.Label(listbox_frame, text="Select a session to load:").pack(anchor=tk.W)
            
            listbox = tk.Listbox(listbox_frame, height=15)
            scrollbar = ttk.Scrollbar(listbox_frame, orient="vertical", command=listbox.yview)
            listbox.configure(yscrollcommand=scrollbar.set)
            
            # Populate listbox with session info
            session_data = []
            for session in sessions:
                household_data = session['user_data'].get('household_data', {})
                name = household_data.get('name', 'Unknown')
                location = household_data.get('location', 'Unknown')
                created = session['created_at']
                session_info = f"{name} - {location} ({created})"
                listbox.insert(tk.END, session_info)
                session_data.append(session)
            
            listbox.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
            scrollbar.pack(side=tk.RIGHT, fill=tk.Y)
            
            # Buttons
            button_frame = ttk.Frame(session_dialog)
            button_frame.pack(fill=tk.X, padx=10, pady=5)
            
            def load_selected():
                selection = listbox.curselection()
                if not selection:
                    messagebox.showwarning("Warning", "Please select a session to load.")
                    return
                
                selected_session = session_data[selection[0]]
                self.populate_form_from_session(selected_session)
                session_dialog.destroy()
            
            def cancel():
                session_dialog.destroy()
            
            ttk.Button(button_frame, text="Load Selected", command=load_selected).pack(side=tk.LEFT, padx=5)
            ttk.Button(button_frame, text="Cancel", command=cancel).pack(side=tk.LEFT, padx=5)
            
        except Exception as e:
            messagebox.showerror("Error", f"Failed to load sessions: {e}")
    
    def populate_form_from_session(self, session):
        """Populate form fields from session data"""
        try:
            household_data = session['user_data'].get('household_data', {})
            
            # Basic info
            self.name_var.set(household_data.get('name', ''))
            self.age_var.set(str(household_data.get('age', '')))
            self.location_var.set(household_data.get('location', ''))
            self.household_size_var.set(str(household_data.get('household_size', '')))
            self.bedrooms_var.set(str(household_data.get('bedrooms', '')))
            self.bathrooms_var.set(str(household_data.get('bathrooms', '')))
            
            # Financial info
            self.rent_var.set(str(household_data.get('rent', '')))
            self.income_var.set(str(household_data.get('monthly_income', '')))
            self.groceries_var.set(str(household_data.get('groceries', '')))
            self.savings_var.set(str(household_data.get('savings', '')))
            
            # Utilities
            utilities = household_data.get('utilities', {})
            self.water_var.set(str(utilities.get('water', '')))
            self.phone_var.set(str(utilities.get('phone', '')))
            self.electricity_var.set(str(utilities.get('electricity', '')))
            self.other_utilities_var.set(str(utilities.get('other', '')))
            
            # Debt - use the correct DebtData structure
            debt = household_data.get('debt', {})
            self.total_debt_var.set(str(debt.get('total_debt', 0.0)))
            self.debt_payment_var.set(str(debt.get('monthly_payment', 0.0)))
            self.debt_type_var.set(debt.get('debt_type', 'No Debt'))
            self.interest_rate_var.set(str(debt.get('interest_rate', 0.0)))
            
            # Monthly payments
            monthly_payments = household_data.get('monthly_payments', [])
            payment_vars = [
                (self.payment1_name_var, self.payment1_amount_var),
                (self.payment2_name_var, self.payment2_amount_var),
                (self.payment3_name_var, self.payment3_amount_var),
                (self.payment4_name_var, self.payment4_amount_var)
            ]
            
            # Clear all payment fields first
            for name_var, amount_var in payment_vars:
                name_var.set("")
                amount_var.set("0.0")
            
            # Populate with existing payments
            for i, payment in enumerate(monthly_payments[:4]):  # Max 4 payments
                if i < len(payment_vars):
                    name_var, amount_var = payment_vars[i]
                    name_var.set(payment.get('name', ''))
                    amount_var.set(str(payment.get('amount', '0.0')))
            
            # Set current session info
            self.current_session_id = session['session_id']
            
            # Update status
            self.status_label.config(text=f"Loaded: {session['session_id']}", foreground="green")
            
            # Calculate and display totals
            self.calculate_totals()
            
            messagebox.showinfo("Success", f"Loaded session: {session['session_id']}")
            
        except Exception as e:
            messagebox.showerror("Error", f"Failed to populate form: {e}")
    
    def clear_form(self):
        """Clear all form inputs"""
        self.name_var.set("")
        self.age_var.set("")
        self.location_var.set("")
        self.household_size_var.set("")
        self.bedrooms_var.set("")
        self.bathrooms_var.set("")
        self.rent_var.set("")
        self.water_var.set("")
        self.phone_var.set("")
        self.electricity_var.set("")
        self.other_utilities_var.set("")
        self.income_var.set("")
        self.groceries_var.set("")
        self.savings_var.set("")
        self.total_debt_var.set("")
        self.debt_payment_var.set("")
        self.debt_type_var.set("")
        self.interest_rate_var.set("")
        self.payment1_name_var.set("")
        self.payment1_amount_var.set("")
        self.payment2_name_var.set("")
        self.payment2_amount_var.set("")
        self.payment3_name_var.set("")
        self.payment3_amount_var.set("")
        self.payment4_name_var.set("")
        self.payment4_amount_var.set("")
        self.totals_text.delete(1.0, tk.END)
        self.current_session_id = None
        self.status_label.config(text="No data loaded", foreground="gray")
    
    def refresh_database(self):
        """Refresh database view"""
        try:
            sessions = db.get_recent_sessions(limit=10)
            
            # Get session count
            session_count = db.get_session_count()
            
            db_content = f"DATABASE SESSIONS (Total: {session_count}):\n"
            db_content += "=" * 50 + "\n"
            db_content += "Note: Database automatically keeps only the 3 most recent sessions\n"
            db_content += "=" * 50 + "\n\n"
            
            if not sessions:
                db_content += "No sessions found in database.\n"
            else:
                for i, session in enumerate(sessions, 1):
                    db_content += f"SESSION {i}:\n"
                    db_content += f"ID: {session['session_id']}\n"
                    db_content += f"Created: {session['created_at']}\n"
                    db_content += f"Updated: {session['updated_at']}\n"
                    
                    if session['user_data']:
                        household_data = session['user_data'].get('household_data', {})
                        db_content += f"Name: {household_data.get('name', 'N/A')}\n"
                        db_content += f"Location: {household_data.get('location', 'N/A')}\n"
                        db_content += f"Monthly Income: ${household_data.get('monthly_income', 0):,.2f}\n"
                        db_content += f"Monthly Expenses: ${household_data.get('rent', 0) + household_data.get('groceries', 0) + household_data.get('debt', {}).get('monthly_payment', 0):,.2f}\n"
                    
                    db_content += "\n" + "-" * 30 + "\n\n"
            
            self.db_text.delete(1.0, tk.END)
            self.db_text.insert(1.0, db_content)
            
        except Exception as e:
            messagebox.showerror("Error", f"Failed to refresh database: {e}")
    
    def clear_database(self):
        """Clear all database sessions"""
        if messagebox.askyesno("Confirm", "Are you sure you want to clear ALL database sessions?\n\nThis action cannot be undone!"):
            try:
                success = db.clear_all_sessions()
                if success:
                    # Clear current session info
                    self.current_session_id = None
                    self.current_household_data = None
                    self.status_label.config(text="Database cleared", foreground="red")
                    
                    messagebox.showinfo("Success", "All database sessions have been cleared successfully.")
                    self.refresh_database()
                else:
                    messagebox.showerror("Error", "Failed to clear database sessions.")
            except Exception as e:
                messagebox.showerror("Error", f"Failed to clear database: {e}")
    
    def test_budget_analysis(self):
        """Test AI budget analysis"""
        if not self.current_household_data:
            messagebox.showwarning("Warning", "Please save data first before testing AI analysis.")
            return
            
        try:
            from gemini_client import GeminiClient
            client = GeminiClient()
            
            # Create budget analysis prompt
            budget_text = f"""
            HOUSEHOLD BUDGET ANALYSIS REQUEST
            
            Personal Information:
            - Name: {self.current_household_data.name}
            - Location: {self.current_household_data.location}
            - Household Size: {self.current_household_data.household_size} people
            - Housing: {self.current_household_data.bedrooms} bedrooms, {self.current_household_data.bathrooms} bathrooms
            
            MONTHLY EXPENSES BREAKDOWN:
            
            Housing Costs:
            - Rent: ${self.current_household_data.rent:.2f}
            - Utilities Total: ${self.current_household_data.total_utilities:.2f}
              * Water: ${self.current_household_data.utilities.water:.2f}
              * Phone: ${self.current_household_data.utilities.phone:.2f}
              * Electricity: ${self.current_household_data.utilities.electricity:.2f}
              * Other Utilities: ${self.current_household_data.utilities.other:.2f}
            
            Living Expenses:
            - Monthly Income: ${self.current_household_data.monthly_income:.2f}
            - Groceries: ${self.current_household_data.groceries:.2f}
            - Savings Goal: ${self.current_household_data.savings:.2f}
            
            Debt Information:
            - Total Debt: ${self.current_household_data.debt.total_debt:.2f}
            - Monthly Debt Payment: ${self.current_household_data.debt.monthly_payment:.2f}
            - Debt Type: {self.current_household_data.debt.debt_type}
            - Interest Rate: {self.current_household_data.debt.interest_rate}%
            
            Additional Monthly Payments:
            """
            
            for i, payment in enumerate(self.current_household_data.monthly_payments, 1):
                budget_text += f"  {i}. {payment.name}: ${payment.amount:.2f} - {'Essential' if payment.is_essential else 'Optional'}\n"
            
            budget_text += f"""
            
            TOTAL MONTHLY EXPENSES: ${self.current_household_data.total_monthly_expenses:.2f}
            
            Please provide a comprehensive analysis including:
            1. Cost of living assessment for {self.current_household_data.location}
            2. Housing cost analysis (rent vs. local averages)
            3. Utility cost breakdown and efficiency recommendations
            4. Grocery budget analysis for {self.current_household_data.household_size} people
            5. Debt management strategy
            6. Savings optimization recommendations
            7. Monthly payment prioritization
            8. Overall budget health score and recommendations
            """
            
            # Get AI analysis
            analysis = client.analyze_budget_data(budget_text)
            
            # Display results
            result = f"BUDGET ANALYSIS RESULTS:\n"
            result += "=" * 50 + "\n\n"
            result += analysis
            
            self.ai_text.delete(1.0, tk.END)
            self.ai_text.insert(1.0, result)
            
        except Exception as e:
            messagebox.showerror("Error", f"Failed to get AI analysis: {e}")
    
    
    def get_average_costs(self):
        """Get average living costs for the specified location"""
        location = self.ai_location_var.get().strip()
        household_size = self.ai_household_size_var.get().strip()
        generation_mode = self.generation_mode.get()
        
        if not location:
            messagebox.showwarning("Warning", "Please enter a location.")
            return
        
        try:
            from gemini_client import GeminiClient
            client = GeminiClient()
            
            # Get baseline estimates for consistency
            baseline_estimates = self.get_location_baseline(location, int(household_size))
            
            if generation_mode == "basic":
                # Basic generation - quick and easy to parse
                cost_prompt = f"give me the range of the average cost of living in an average/median {household_size} bedroom/ 1.5 bath living space in {location} based on recent sources in the format of the text below with no other text or notes \n 'rent: number - number, utilities: number - number, groceries: number - number'"
                
                # Get AI analysis
                cost_analysis = client.analyze_budget_data(cost_prompt)
                
                # Store the data for potential use
                self.ai_cost_data = {
                    'location': location,
                    'household_size': household_size,
                    'analysis': cost_analysis,
                    'baseline_estimates': baseline_estimates,
                    'mode': 'basic'
                }
                
                # Display results
                result = f"BASIC COST RANGES FOR {location.upper()}:\n"
                result += f"Household Size: {household_size} people\n"
                result += "=" * 50 + "\n\n"
                result += "BASELINE ESTIMATES:\n"
                result += f"• Rent: ${baseline_estimates['rent']:,.0f}\n"
                result += f"• Income: ${baseline_estimates['income']:,.0f}\n"
                result += f"• Groceries: ${baseline_estimates['groceries']:,.0f}\n"
                result += f"• Utilities: Water ${baseline_estimates['utilities']['water']:,.0f}, Electricity ${baseline_estimates['utilities']['electricity']:,.0f}, Phone ${baseline_estimates['utilities']['phone']:,.0f}\n\n"
                result += "AI COST RANGES:\n"
                result += "-" * 30 + "\n"
                result += cost_analysis
                
            else:
                # Advanced generation - detailed breakdown
                cost_prompt = f"Give me a cost break down average cost of the monthly payments for {household_size} people paying for the average phone bill (no MVNO), cost of owning a car, health insurance in {location} based on recent sources only a single number per category in the format of the text below with no other text or notes \n 'Phone: number,Car: number,Health Insurance: number'"
                
                # Get AI analysis
                cost_analysis = client.analyze_budget_data(cost_prompt)
                
                # Store the data for potential use
                self.ai_cost_data = {
                    'location': location,
                    'household_size': household_size,
                    'analysis': cost_analysis,
                    'baseline_estimates': baseline_estimates,
                    'mode': 'advanced'
                }
                
                # Display results
                result = f"ADVANCED COST BREAKDOWN FOR {location.upper()}:\n"
                result += f"Household Size: {household_size} people\n"
                result += "=" * 50 + "\n\n"
                result += "BASELINE ESTIMATES:\n"
                result += f"• Rent: ${baseline_estimates['rent']:,.0f}\n"
                result += f"• Income: ${baseline_estimates['income']:,.0f}\n"
                result += f"• Groceries: ${baseline_estimates['groceries']:,.0f}\n"
                result += f"• Utilities: Water ${baseline_estimates['utilities']['water']:,.0f}, Electricity ${baseline_estimates['utilities']['electricity']:,.0f}, Phone ${baseline_estimates['utilities']['phone']:,.0f}\n\n"
                result += "AI DETAILED BREAKDOWN:\n"
                result += "-" * 30 + "\n"
                result += cost_analysis
            
            self.cost_text.delete(1.0, tk.END)
            self.cost_text.insert(1.0, result)
            
            messagebox.showinfo("Success", f"Average living costs retrieved for {location} ({generation_mode} mode)")
            
        except Exception as e:
            messagebox.showerror("Error", f"Failed to get average living costs: {e}")
    
    def apply_costs_to_form(self):
        """Apply AI cost data to the main form with structured data extraction"""
        if not self.ai_cost_data:
            messagebox.showwarning("Warning", "Please get average living costs first.")
            return
            
        try:
            # Update basic info
            self.location_var.set(self.ai_cost_data['location'])
            self.household_size_var.set(self.ai_cost_data['household_size'])
            
            # Extract structured data from AI analysis
            extracted_data = self.extract_structured_costs(self.ai_cost_data['analysis'])
            
            # Apply extracted data to form fields
            if 'rent' in extracted_data:
                self.rent_var.set(str(int(extracted_data['rent'])))
            
            if 'utilities' in extracted_data:
                utils = extracted_data['utilities']
                if 'water' in utils:
                    self.water_var.set(str(int(utils['water'])))
                if 'electricity' in utils:
                    self.electricity_var.set(str(int(utils['electricity'])))
                if 'phone' in utils:
                    self.phone_var.set(str(int(utils['phone'])))
                if 'other' in utils:
                    self.other_utilities_var.set(str(int(utils['other'])))
            
            if 'groceries' in extracted_data:
                self.groceries_var.set(str(int(extracted_data['groceries'])))
            
            if 'income' in extracted_data:
                self.income_var.set(str(int(extracted_data['income'])))
            
            if 'savings' in extracted_data:
                self.savings_var.set(str(int(extracted_data['savings'])))
            
            # Handle advanced mode specific data
            if 'car_payment' in extracted_data:
                # Set first monthly payment to car payment
                self.payment1_name_var.set("Car Payment")
                self.payment1_amount_var.set(str(int(extracted_data['car_payment'])))
            
            if 'health_insurance' in extracted_data:
                # Set second monthly payment to health insurance
                self.payment2_name_var.set("Health Insurance")
                self.payment2_amount_var.set(str(int(extracted_data['health_insurance'])))
            
            # Set default values for missing fields
            if not self.age_var.get():
                self.age_var.set("30")  # Default age
            if not self.bedrooms_var.get():
                self.bedrooms_var.set(self.ai_cost_data['household_size'])  # Default bedrooms
            if not self.bathrooms_var.get():
                self.bathrooms_var.set("1.5")  # Default bathrooms
            
            # Calculate totals to show the user
            self.calculate_totals()
            
            messagebox.showinfo("Success", f"Applied structured cost data to form.\n\nExtracted data:\n{self.format_extracted_data(extracted_data)}\n\nPlease review and adjust values as needed for your specific situation.")
            
            # Switch to input tab to show the updated form
            self.notebook.select(0)  # Switch to first tab (Input Data)
            
        except Exception as e:
            messagebox.showerror("Error", f"Failed to apply costs to form: {e}")
    
    def extract_structured_costs(self, analysis_text):
        """Extract structured cost data from AI analysis text with validation"""
        import re
        
        extracted = {}
        text = analysis_text.lower()
        
        # Check if this is basic mode (ranges) or advanced mode (single numbers)
        is_basic_mode = 'rent:' in text and '-' in text
        
        if is_basic_mode:
            # Basic mode: extract ranges and use midpoint
            # Rent range: "rent: 1200 - 1800"
            rent_range_match = re.search(r'rent:\s*([0-9,]+)\s*-\s*([0-9,]+)', text)
            if rent_range_match:
                try:
                    min_rent = float(rent_range_match.group(1).replace(',', ''))
                    max_rent = float(rent_range_match.group(2).replace(',', ''))
                    extracted['rent'] = (min_rent + max_rent) / 2  # Use midpoint
                except:
                    pass
            
            # Utilities range: "utilities: 200 - 300"
            utilities_range_match = re.search(r'utilities:\s*([0-9,]+)\s*-\s*([0-9,]+)', text)
            if utilities_range_match:
                try:
                    min_utils = float(utilities_range_match.group(1).replace(',', ''))
                    max_utils = float(utilities_range_match.group(2).replace(',', ''))
                    total_utils = (min_utils + max_utils) / 2
                    # Distribute utilities proportionally
                    extracted['utilities'] = {
                        'water': total_utils * 0.2,
                        'electricity': total_utils * 0.4,
                        'phone': total_utils * 0.3,
                        'other': total_utils * 0.1
                    }
                except:
                    pass
            
            # Groceries range: "groceries: 400 - 600"
            groceries_range_match = re.search(r'groceries:\s*([0-9,]+)\s*-\s*([0-9,]+)', text)
            if groceries_range_match:
                try:
                    min_groceries = float(groceries_range_match.group(1).replace(',', ''))
                    max_groceries = float(groceries_range_match.group(2).replace(',', ''))
                    extracted['groceries'] = (min_groceries + max_groceries) / 2
                except:
                    pass
        
        else:
            # Advanced mode: extract single numbers
            # Phone: number
            phone_match = re.search(r'phone:\s*([0-9,]+)', text)
            if phone_match:
                try:
                    phone_value = float(phone_match.group(1).replace(',', ''))
                    if 30 <= phone_value <= 150:
                        extracted['utilities'] = extracted.get('utilities', {})
                        extracted['utilities']['phone'] = phone_value
                except:
                    pass
            
            # Car: number
            car_match = re.search(r'car:\s*([0-9,]+)', text)
            if car_match:
                try:
                    car_value = float(car_match.group(1).replace(',', ''))
                    if 200 <= car_value <= 800:
                        # Add as monthly payment
                        extracted['car_payment'] = car_value
                except:
                    pass
            
            # Health Insurance: number
            health_match = re.search(r'health insurance:\s*([0-9,]+)', text)
            if health_match:
                try:
                    health_value = float(health_match.group(1).replace(',', ''))
                    if 100 <= health_value <= 600:
                        # Add as monthly payment
                        extracted['health_insurance'] = health_value
                except:
                    pass
        
        # Fallback: Extract rent/housing costs with validation (for both modes)
        if 'rent' not in extracted:
            rent_patterns = [
                r'rent[:\s]*\$?([0-9,]+)',
                r'housing[:\s]*\$?([0-9,]+)',
                r'apartment[:\s]*\$?([0-9,]+)',
                r'home[:\s]*\$?([0-9,]+)'
            ]
            for pattern in rent_patterns:
                match = re.search(pattern, text)
                if match:
                    try:
                        rent_value = float(match.group(1).replace(',', ''))
                        # Validate rent is reasonable (between $500 and $5000)
                        if 500 <= rent_value <= 5000:
                            extracted['rent'] = rent_value
                            break
                    except:
                        continue
        
        # Extract utilities
        utilities = {}
        
        # Water with validation
        water_patterns = [r'water[:\s]*\$?([0-9,]+)', r'sewer[:\s]*\$?([0-9,]+)']
        for pattern in water_patterns:
            match = re.search(pattern, text)
            if match:
                try:
                    water_value = float(match.group(1).replace(',', ''))
                    # Validate water cost is reasonable (between $20 and $200)
                    if 20 <= water_value <= 200:
                        utilities['water'] = water_value
                        break
                except:
                    continue
        
        # Electricity with validation
        electric_patterns = [r'electricity[:\s]*\$?([0-9,]+)', r'power[:\s]*\$?([0-9,]+)', r'gas[:\s]*\$?([0-9,]+)']
        for pattern in electric_patterns:
            match = re.search(pattern, text)
            if match:
                try:
                    electric_value = float(match.group(1).replace(',', ''))
                    # Validate electricity cost is reasonable (between $50 and $300)
                    if 50 <= electric_value <= 300:
                        utilities['electricity'] = electric_value
                        break
                except:
                    continue
        
        # Phone/Internet with validation
        phone_patterns = [r'phone[:\s]*\$?([0-9,]+)', r'internet[:\s]*\$?([0-9,]+)', r'cable[:\s]*\$?([0-9,]+)']
        for pattern in phone_patterns:
            match = re.search(pattern, text)
            if match:
                try:
                    phone_value = float(match.group(1).replace(',', ''))
                    # Validate phone/internet cost is reasonable (between $30 and $150)
                    if 30 <= phone_value <= 150:
                        utilities['phone'] = phone_value
                        break
                except:
                    continue
        
        if utilities:
            extracted['utilities'] = utilities
        
        # Extract groceries with validation
        grocery_patterns = [r'grocer[ies]*[:\s]*\$?([0-9,]+)', r'food[:\s]*\$?([0-9,]+)']
        for pattern in grocery_patterns:
            match = re.search(pattern, text)
            if match:
                try:
                    grocery_value = float(match.group(1).replace(',', ''))
                    # Validate groceries are reasonable (between $200 and $1000)
                    if 200 <= grocery_value <= 1000:
                        extracted['groceries'] = grocery_value
                        break
                except:
                    continue
        
        # Extract income with validation
        income_patterns = [r'income[:\s]*\$?([0-9,]+)', r'salary[:\s]*\$?([0-9,]+)', r'wage[:\s]*\$?([0-9,]+)']
        for pattern in income_patterns:
            match = re.search(pattern, text)
            if match:
                try:
                    income_value = float(match.group(1).replace(',', ''))
                    # Validate income is reasonable (between $2000 and $15000 monthly)
                    if 2000 <= income_value <= 15000:
                        extracted['income'] = income_value
                        break
                except:
                    continue
        
        # Calculate suggested savings (typically 10-20% of income)
        if 'income' in extracted:
            suggested_savings = extracted['income'] * 0.15  # 15% of income
            extracted['savings'] = suggested_savings
        
        # Use baseline estimates as fallbacks if AI extraction failed
        if hasattr(self, 'ai_cost_data') and self.ai_cost_data and 'baseline_estimates' in self.ai_cost_data:
            baseline = self.ai_cost_data['baseline_estimates']
            
            # Fill in missing values with baseline estimates
            if 'rent' not in extracted:
                extracted['rent'] = baseline['rent']
            if 'income' not in extracted:
                extracted['income'] = baseline['income']
            if 'groceries' not in extracted:
                extracted['groceries'] = baseline['groceries']
            if 'utilities' not in extracted:
                extracted['utilities'] = baseline['utilities']
            if 'savings' not in extracted:
                extracted['savings'] = baseline['savings']
        
        # Validate consistency between extracted values
        extracted = self.validate_cost_consistency(extracted)
        
        return extracted
    
    def validate_cost_consistency(self, extracted_data):
        """Validate and adjust extracted data for consistency"""
        # If we have both rent and income, ensure rent is reasonable (25-35% of income)
        if 'rent' in extracted_data and 'income' in extracted_data:
            rent_ratio = extracted_data['rent'] / extracted_data['income']
            if rent_ratio > 0.4:  # If rent is more than 40% of income, adjust
                extracted_data['rent'] = extracted_data['income'] * 0.3  # Set to 30%
            elif rent_ratio < 0.2:  # If rent is less than 20% of income, adjust
                extracted_data['rent'] = extracted_data['income'] * 0.25  # Set to 25%
        
        # If we have income but no rent, estimate rent based on income
        if 'income' in extracted_data and 'rent' not in extracted_data:
            extracted_data['rent'] = extracted_data['income'] * 0.3  # 30% of income
        
        # If we have rent but no income, estimate income based on rent
        if 'rent' in extracted_data and 'income' not in extracted_data:
            extracted_data['income'] = extracted_data['rent'] / 0.3  # Rent should be 30% of income
        
        # Ensure utilities are reasonable relative to income
        if 'income' in extracted_data and 'utilities' in extracted_data:
            total_utilities = sum(extracted_data['utilities'].values())
            utility_ratio = total_utilities / extracted_data['income']
            if utility_ratio > 0.15:  # If utilities are more than 15% of income, cap them
                scale_factor = 0.1 / utility_ratio  # Scale to 10% of income
                for key in extracted_data['utilities']:
                    extracted_data['utilities'][key] *= scale_factor
        
        # Ensure groceries are reasonable relative to household size
        if 'groceries' in extracted_data:
            # Assume groceries should be $300-600 per person per month
            # If we have household size from AI data, validate against it
            # For now, ensure groceries are between $200 and $1000
            if extracted_data['groceries'] < 200:
                extracted_data['groceries'] = 300
            elif extracted_data['groceries'] > 1000:
                extracted_data['groceries'] = 600
        
        return extracted_data
    
    def get_location_baseline(self, location, household_size):
        """Get baseline cost estimates for a location"""
        location_key = location.lower().strip()
        
        # Find matching location in baselines
        baseline = None
        for key, data in self.location_baselines.items():
            if key in location_key or location_key in key:
                baseline = data
                break
        
        # If no exact match, use Austin, TX as default
        if not baseline:
            baseline = self.location_baselines['austin, tx']
        
        # Base costs (Austin, TX baseline)
        base_rent = 1200 + (household_size - 1) * 200  # $1200 for 1 person, +$200 per additional
        base_income = 4000 + (household_size - 1) * 1000  # $4000 for 1 person, +$1000 per additional
        base_groceries = 300 + (household_size - 1) * 200  # $300 for 1 person, +$200 per additional
        
        # Apply location multipliers
        estimated_rent = base_rent * baseline['rent_multiplier']
        estimated_income = base_income * baseline['income_multiplier']
        estimated_groceries = base_groceries * baseline['cost_index']
        
        # Utilities scale with cost index
        estimated_utilities = {
            'water': 50 * baseline['cost_index'],
            'electricity': 100 * baseline['cost_index'],
            'phone': 80 * baseline['cost_index'],
            'other': 30 * baseline['cost_index']
        }
        
        return {
            'rent': round(estimated_rent, 0),
            'income': round(estimated_income, 0),
            'groceries': round(estimated_groceries, 0),
            'utilities': {k: round(v, 0) for k, v in estimated_utilities.items()},
            'savings': round(estimated_income * 0.15, 0)
        }
    
    def format_extracted_data(self, data):
        """Format extracted data for display"""
        formatted = []
        
        if 'rent' in data:
            formatted.append(f"Rent: ${data['rent']:,.2f}")
        
        if 'utilities' in data:
            formatted.append("Utilities:")
            for util, amount in data['utilities'].items():
                formatted.append(f"  - {util.title()}: ${amount:,.2f}")
        
        if 'groceries' in data:
            formatted.append(f"Groceries: ${data['groceries']:,.2f}")
        
        if 'income' in data:
            formatted.append(f"Income: ${data['income']:,.2f}")
        
        if 'savings' in data:
            formatted.append(f"Suggested Savings: ${data['savings']:,.2f}")
        
        return "\n".join(formatted)
    
    def save_from_ai_tab(self):
        """Save current form data from AI tab"""
        # Switch to input tab first to ensure we have the latest data
        self.notebook.select(0)
        # Then call the regular save method
        self.save_data()
    
    def create_and_save_profile(self):
        """Create a complete household profile from AI data and save it directly"""
        if not self.ai_cost_data:
            messagebox.showwarning("Warning", "Please get average living costs first.")
            return
            
        try:
            # Extract structured data
            extracted_data = self.extract_structured_costs(self.ai_cost_data['analysis'])
            
            # Create a complete household data object
            from models import HouseholdData, UtilitiesData, DebtData, MonthlyPayment
            
            # Set up utilities data
            utilities_data = UtilitiesData(
                water=extracted_data.get('utilities', {}).get('water', 50.0),
                electricity=extracted_data.get('utilities', {}).get('electricity', 100.0),
                phone=extracted_data.get('utilities', {}).get('phone', 80.0),
                other=extracted_data.get('utilities', {}).get('other', 30.0)
            )
            
            # Set up debt data (default values)
            debt_data = DebtData(
                total_debt=0.0,
                monthly_payment=0.0,
                debt_type="No Debt",
                interest_rate=0.0
            )
            
            # Set up monthly payments (default values)
            monthly_payments = [
                MonthlyPayment(name="Car Payment", amount=300.0),
                MonthlyPayment(name="Insurance", amount=150.0),
                MonthlyPayment(name="Subscriptions", amount=50.0),
                MonthlyPayment(name="Other", amount=100.0)
            ]
            
            # Use profile data if available, otherwise use defaults
            if self.current_profile:
                name = self.current_profile.get('name', f"AI Generated - {self.ai_cost_data['location']}")
                age = self.current_profile.get('age', 30)
                bedrooms = self.current_profile.get('bedrooms', int(self.ai_cost_data['household_size']))
                bathrooms = self.current_profile.get('bathrooms', 1.5)
                # Use profile utilities as base, but allow AI to override if found
                utilities_data = UtilitiesData(
                    water=extracted_data.get('utilities', {}).get('water', self.current_profile.get('default_utilities', {}).get('water', 50.0)),
                    electricity=extracted_data.get('utilities', {}).get('electricity', self.current_profile.get('default_utilities', {}).get('electricity', 100.0)),
                    phone=extracted_data.get('utilities', {}).get('phone', self.current_profile.get('default_utilities', {}).get('phone', 80.0)),
                    other=extracted_data.get('utilities', {}).get('other', self.current_profile.get('default_utilities', {}).get('other', 30.0))
                )
                # Use profile debt as base
                debt_data = DebtData(
                    total_debt=self.current_profile.get('default_debt', {}).get('total_debt', 0.0),
                    monthly_payment=self.current_profile.get('default_debt', {}).get('monthly_payment', 0.0),
                    debt_type=self.current_profile.get('default_debt', {}).get('debt_type', 'No Debt'),
                    interest_rate=self.current_profile.get('default_debt', {}).get('interest_rate', 0.0)
                )
                # Use profile monthly payments
                profile_payments = self.current_profile.get('default_monthly_payments', [])
                monthly_payments = [MonthlyPayment(name=p.get('name', ''), amount=p.get('amount', 0.0)) for p in profile_payments]
            else:
                name = f"AI Generated - {self.ai_cost_data['location']}"
                age = 30
                bedrooms = int(self.ai_cost_data['household_size'])
                bathrooms = 1.5
            
            # Create household data
            household_data = HouseholdData(
                name=name,
                age=age,
                location=self.ai_cost_data['location'],
                household_size=int(self.ai_cost_data['household_size']),
                bedrooms=bedrooms,
                bathrooms=bathrooms,
                rent=extracted_data.get('rent', 1200.0),
                utilities=utilities_data,
                monthly_income=extracted_data.get('income', 4000.0),
                groceries=extracted_data.get('groceries', 400.0),
                savings=extracted_data.get('savings', 600.0),
                debt=debt_data,
                monthly_payments=monthly_payments
            )
            
            # Save to database
            user_data = {
                "household_data": household_data.model_dump()
            }
            
            session_id = f"ai_profile_{uuid.uuid4().hex[:12]}"
            success = db.create_session(session_id, user_data)
            
            if success:
                messagebox.showinfo("Success", f"Complete AI-generated profile saved successfully!\n\nSession ID: {session_id}\n\nProfile includes:\n- Location: {household_data.location}\n- Household Size: {household_data.household_size}\n- Monthly Income: ${household_data.monthly_income:,.2f}\n- Rent: ${household_data.rent:,.2f}\n- Total Monthly Expenses: ${household_data.total_monthly_expenses:,.2f}\n\nNote: Database automatically keeps only the 3 most recent sessions.")
                
                # Refresh database view
                self.root.after(100, self.refresh_database)
            else:
                messagebox.showerror("Error", "Failed to save AI-generated profile.")
                
        except Exception as e:
            messagebox.showerror("Error", f"Failed to create and save profile: {e}")
    
    def preview_data_structure(self):
        """Preview the data structure that would be created from AI analysis"""
        if not self.ai_cost_data:
            messagebox.showwarning("Warning", "Please get average living costs first.")
            return
            
        try:
            # Extract structured data
            extracted_data = self.extract_structured_costs(self.ai_cost_data['analysis'])
            
            # Create preview text
            preview = f"PREVIEW: Data Structure for {self.ai_cost_data['location']}\n"
            preview += "=" * 50 + "\n\n"
            
            preview += "BASIC INFO:\n"
            preview += f"  Name: AI Generated - {self.ai_cost_data['location']}\n"
            preview += f"  Age: 30 (default)\n"
            preview += f"  Location: {self.ai_cost_data['location']}\n"
            preview += f"  Household Size: {self.ai_cost_data['household_size']}\n"
            preview += f"  Bedrooms: {self.ai_cost_data['household_size']}\n"
            preview += f"  Bathrooms: 1.5\n\n"
            
            preview += "FINANCIAL DATA:\n"
            if 'rent' in extracted_data:
                preview += f"  Rent: ${extracted_data['rent']:,.2f}\n"
            if 'income' in extracted_data:
                preview += f"  Monthly Income: ${extracted_data['income']:,.2f}\n"
            if 'groceries' in extracted_data:
                preview += f"  Groceries: ${extracted_data['groceries']:,.2f}\n"
            if 'savings' in extracted_data:
                preview += f"  Savings: ${extracted_data['savings']:,.2f}\n"
            
            preview += "\nUTILITIES:\n"
            if 'utilities' in extracted_data:
                for util, amount in extracted_data['utilities'].items():
                    preview += f"  {util.title()}: ${amount:,.2f}\n"
            else:
                preview += "  Water: $50.00 (default)\n"
                preview += "  Electricity: $100.00 (default)\n"
                preview += "  Phone: $80.00 (default)\n"
                preview += "  Other: $30.00 (default)\n"
            
            preview += "\nDEFAULT VALUES:\n"
            preview += "  Debt: $0.00 (all categories)\n"
            preview += "  Monthly Payments: Car ($300), Insurance ($150), Subscriptions ($50), Other ($100)\n"
            
            # Show in a new window
            preview_window = tk.Toplevel(self.root)
            preview_window.title("Data Structure Preview")
            preview_window.geometry("600x500")
            
            text_widget = scrolledtext.ScrolledText(preview_window, wrap=tk.WORD)
            text_widget.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
            text_widget.insert(1.0, preview)
            text_widget.config(state=tk.DISABLED)
            
        except Exception as e:
            messagebox.showerror("Error", f"Failed to preview data structure: {e}")
    
    def clear_ai_results(self):
        """Clear AI test results"""
        self.ai_text.delete(1.0, tk.END)
        self.cost_text.delete(1.0, tk.END)
        self.ai_cost_data = None
    
    def create_profile_tab(self):
        """Create the profile management tab"""
        profile_frame = ttk.Frame(self.notebook)
        self.notebook.add(profile_frame, text="Profile Management")
        
        # Create scrollable frame
        canvas = tk.Canvas(profile_frame)
        scrollbar = ttk.Scrollbar(profile_frame, orient="vertical", command=canvas.yview)
        scrollable_frame = ttk.Frame(canvas)
        
        scrollable_frame.bind(
            "<Configure>",
            lambda e: canvas.configure(scrollregion=canvas.bbox("all"))
        )
        
        canvas.create_window((0, 0), window=scrollable_frame, anchor="nw")
        canvas.configure(yscrollcommand=scrollbar.set)
        
        # Profile Selection Section
        selection_frame = ttk.LabelFrame(scrollable_frame, text="Profile Selection", padding=10)
        selection_frame.grid(row=0, column=0, columnspan=2, sticky="ew", pady=5)
        
        ttk.Label(selection_frame, text="Current Profile:").grid(row=0, column=0, sticky="w")
        self.current_profile_var = tk.StringVar(value="No Profile Selected")
        ttk.Label(selection_frame, textvariable=self.current_profile_var, foreground="blue").grid(row=0, column=1, sticky="w", padx=10)
        
        button_frame1 = ttk.Frame(selection_frame)
        button_frame1.grid(row=1, column=0, columnspan=2, pady=10)
        
        ttk.Button(button_frame1, text="Load Profile", command=self.load_profile_dialog).pack(side=tk.LEFT, padx=5)
        ttk.Button(button_frame1, text="Create New Profile", command=self.create_profile_dialog).pack(side=tk.LEFT, padx=5)
        ttk.Button(button_frame1, text="Apply to Form", command=self.apply_profile_to_form).pack(side=tk.LEFT, padx=5)
        
        # Profile Creation/Editing Section
        edit_frame = ttk.LabelFrame(scrollable_frame, text="Profile Data", padding=10)
        edit_frame.grid(row=1, column=0, columnspan=2, sticky="ew", pady=5)
        
        # Profile name
        ttk.Label(edit_frame, text="Profile Name:").grid(row=0, column=0, sticky="w")
        self.profile_name_var = tk.StringVar()
        ttk.Entry(edit_frame, textvariable=self.profile_name_var, width=30).grid(row=0, column=1, padx=5)
        
        # Personal info
        ttk.Label(edit_frame, text="Name:").grid(row=1, column=0, sticky="w")
        self.profile_user_name_var = tk.StringVar()
        ttk.Entry(edit_frame, textvariable=self.profile_user_name_var, width=30).grid(row=1, column=1, padx=5)
        
        ttk.Label(edit_frame, text="Age:").grid(row=2, column=0, sticky="w")
        self.profile_age_var = tk.StringVar()
        ttk.Entry(edit_frame, textvariable=self.profile_age_var, width=10).grid(row=2, column=1, sticky="w", padx=5)
        
        ttk.Label(edit_frame, text="Location:").grid(row=3, column=0, sticky="w")
        self.profile_location_var = tk.StringVar()
        ttk.Entry(edit_frame, textvariable=self.profile_location_var, width=30).grid(row=3, column=1, padx=5)
        
        # Household info
        ttk.Label(edit_frame, text="Household Size:").grid(row=4, column=0, sticky="w")
        self.profile_household_size_var = tk.StringVar()
        ttk.Entry(edit_frame, textvariable=self.profile_household_size_var, width=10).grid(row=4, column=1, sticky="w", padx=5)
        
        ttk.Label(edit_frame, text="Bedrooms:").grid(row=5, column=0, sticky="w")
        self.profile_bedrooms_var = tk.StringVar()
        ttk.Entry(edit_frame, textvariable=self.profile_bedrooms_var, width=10).grid(row=5, column=1, sticky="w", padx=5)
        
        ttk.Label(edit_frame, text="Bathrooms:").grid(row=6, column=0, sticky="w")
        self.profile_bathrooms_var = tk.StringVar()
        ttk.Entry(edit_frame, textvariable=self.profile_bathrooms_var, width=10).grid(row=6, column=1, sticky="w", padx=5)
        
        # Default utilities
        utilities_frame = ttk.LabelFrame(edit_frame, text="Default Utilities", padding=5)
        utilities_frame.grid(row=7, column=0, columnspan=2, sticky="ew", pady=10)
        
        ttk.Label(utilities_frame, text="Water:").grid(row=0, column=0, sticky="w")
        self.profile_water_var = tk.StringVar(value="50.0")
        ttk.Entry(utilities_frame, textvariable=self.profile_water_var, width=10).grid(row=0, column=1, padx=5)
        
        ttk.Label(utilities_frame, text="Electricity:").grid(row=0, column=2, sticky="w", padx=(20,0))
        self.profile_electricity_var = tk.StringVar(value="100.0")
        ttk.Entry(utilities_frame, textvariable=self.profile_electricity_var, width=10).grid(row=0, column=3, padx=5)
        
        ttk.Label(utilities_frame, text="Phone:").grid(row=1, column=0, sticky="w")
        self.profile_phone_var = tk.StringVar(value="80.0")
        ttk.Entry(utilities_frame, textvariable=self.profile_phone_var, width=10).grid(row=1, column=1, padx=5)
        
        ttk.Label(utilities_frame, text="Other:").grid(row=1, column=2, sticky="w", padx=(20,0))
        self.profile_other_utilities_var = tk.StringVar(value="30.0")
        ttk.Entry(utilities_frame, textvariable=self.profile_other_utilities_var, width=10).grid(row=1, column=3, padx=5)
        
        # Default debt
        debt_frame = ttk.LabelFrame(edit_frame, text="Default Debt", padding=5)
        debt_frame.grid(row=8, column=0, columnspan=2, sticky="ew", pady=10)
        
        ttk.Label(debt_frame, text="Total Debt:").grid(row=0, column=0, sticky="w")
        self.profile_total_debt_var = tk.StringVar(value="0.0")
        ttk.Entry(debt_frame, textvariable=self.profile_total_debt_var, width=10).grid(row=0, column=1, padx=5)
        
        ttk.Label(debt_frame, text="Monthly Payment:").grid(row=0, column=2, sticky="w", padx=(20,0))
        self.profile_debt_payment_var = tk.StringVar(value="0.0")
        ttk.Entry(debt_frame, textvariable=self.profile_debt_payment_var, width=10).grid(row=0, column=3, padx=5)
        
        ttk.Label(debt_frame, text="Debt Type:").grid(row=1, column=0, sticky="w")
        self.profile_debt_type_var = tk.StringVar(value="No Debt")
        debt_type_combo = ttk.Combobox(debt_frame, textvariable=self.profile_debt_type_var, width=15)
        debt_type_combo['values'] = ('No Debt', 'Student Loan', 'Credit Card', 'Car Loan', 'Mortgage', 'Personal Loan', 'Other')
        debt_type_combo.grid(row=1, column=1, padx=5)
        
        ttk.Label(debt_frame, text="Interest Rate:").grid(row=1, column=2, sticky="w", padx=(20,0))
        self.profile_interest_rate_var = tk.StringVar(value="0.0")
        ttk.Entry(debt_frame, textvariable=self.profile_interest_rate_var, width=10).grid(row=1, column=3, padx=5)
        
        # Default monthly payments
        payments_frame = ttk.LabelFrame(edit_frame, text="Default Monthly Payments", padding=5)
        payments_frame.grid(row=9, column=0, columnspan=2, sticky="ew", pady=10)
        
        payment_fields = []
        for i in range(4):
            ttk.Label(payments_frame, text=f"Payment {i+1}:").grid(row=i, column=0, sticky="w")
            name_var = tk.StringVar()
            amount_var = tk.StringVar(value="0.0")
            ttk.Entry(payments_frame, textvariable=name_var, width=20).grid(row=i, column=1, padx=5)
            ttk.Entry(payments_frame, textvariable=amount_var, width=10).grid(row=i, column=2, padx=5)
            payment_fields.append((name_var, amount_var))
        
        self.profile_payment_vars = payment_fields
        
        # Savings rate
        ttk.Label(edit_frame, text="Preferred Savings Rate (%):").grid(row=10, column=0, sticky="w")
        self.profile_savings_rate_var = tk.StringVar(value="15.0")
        ttk.Entry(edit_frame, textvariable=self.profile_savings_rate_var, width=10).grid(row=10, column=1, sticky="w", padx=5)
        
        # Action buttons
        action_frame = ttk.Frame(edit_frame)
        action_frame.grid(row=11, column=0, columnspan=2, pady=20)
        
        ttk.Button(action_frame, text="Save Profile", command=self.save_profile).pack(side=tk.LEFT, padx=5)
        ttk.Button(action_frame, text="Update Profile", command=self.update_profile).pack(side=tk.LEFT, padx=5)
        ttk.Button(action_frame, text="Delete Profile", command=self.delete_profile_dialog).pack(side=tk.LEFT, padx=5)
        ttk.Button(action_frame, text="Clear Form", command=self.clear_profile_form).pack(side=tk.LEFT, padx=5)
        
        # Pack canvas and scrollbar
        canvas.pack(side="left", fill="both", expand=True)
        scrollbar.pack(side="right", fill="y")
    
    def load_profile_dialog(self):
        """Show dialog to select and load a profile"""
        try:
            profiles = db.get_all_profiles()
            
            if not profiles:
                messagebox.showinfo("Info", "No profiles found. Create a new profile first.")
                return
            
            # Create profile selection dialog
            profile_dialog = tk.Toplevel(self.root)
            profile_dialog.title("Select Profile to Load")
            profile_dialog.geometry("500x300")
            profile_dialog.transient(self.root)
            profile_dialog.grab_set()
            
            # Create listbox with profiles
            listbox_frame = ttk.Frame(profile_dialog)
            listbox_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
            
            ttk.Label(listbox_frame, text="Select a profile to load:").pack(anchor=tk.W)
            
            listbox = tk.Listbox(listbox_frame, height=10)
            scrollbar = ttk.Scrollbar(listbox_frame, orient="vertical", command=listbox.yview)
            listbox.configure(yscrollcommand=scrollbar.set)
            
            # Populate listbox with profile info
            for profile in profiles:
                profile_info = f"{profile['profile_name']} - {profile.get('name', 'Unknown')} ({profile['created_at']})"
                listbox.insert(tk.END, profile_info)
            
            listbox.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
            scrollbar.pack(side=tk.RIGHT, fill=tk.Y)
            
            # Buttons
            button_frame = ttk.Frame(profile_dialog)
            button_frame.pack(fill=tk.X, padx=10, pady=5)
            
            def load_selected():
                selection = listbox.curselection()
                if not selection:
                    messagebox.showwarning("Warning", "Please select a profile to load.")
                    return
                
                selected_profile = profiles[selection[0]]
                self.load_profile_data(selected_profile)
                profile_dialog.destroy()
            
            def cancel():
                profile_dialog.destroy()
            
            ttk.Button(button_frame, text="Load Selected", command=load_selected).pack(side=tk.LEFT, padx=5)
            ttk.Button(button_frame, text="Cancel", command=cancel).pack(side=tk.LEFT, padx=5)
            
        except Exception as e:
            messagebox.showerror("Error", f"Failed to load profiles: {e}")
    
    def load_profile_data(self, profile_data):
        """Load profile data into the profile form"""
        try:
            # Load basic info
            self.profile_name_var.set(profile_data.get('profile_name', ''))
            self.profile_user_name_var.set(profile_data.get('name', ''))
            self.profile_age_var.set(str(profile_data.get('age', '')))
            self.profile_location_var.set(profile_data.get('location', ''))
            self.profile_household_size_var.set(str(profile_data.get('household_size', '')))
            self.profile_bedrooms_var.set(str(profile_data.get('bedrooms', '')))
            self.profile_bathrooms_var.set(str(profile_data.get('bathrooms', '')))
            
            # Load utilities
            utilities = profile_data.get('default_utilities', {})
            self.profile_water_var.set(str(utilities.get('water', 50.0)))
            self.profile_electricity_var.set(str(utilities.get('electricity', 100.0)))
            self.profile_phone_var.set(str(utilities.get('phone', 80.0)))
            self.profile_other_utilities_var.set(str(utilities.get('other', 30.0)))
            
            # Load debt
            debt = profile_data.get('default_debt', {})
            self.profile_total_debt_var.set(str(debt.get('total_debt', 0.0)))
            self.profile_debt_payment_var.set(str(debt.get('monthly_payment', 0.0)))
            self.profile_debt_type_var.set(debt.get('debt_type', 'No Debt'))
            self.profile_interest_rate_var.set(str(debt.get('interest_rate', 0.0)))
            
            # Load monthly payments
            monthly_payments = profile_data.get('default_monthly_payments', [])
            for i, payment in enumerate(monthly_payments[:4]):
                if i < len(self.profile_payment_vars):
                    name_var, amount_var = self.profile_payment_vars[i]
                    name_var.set(payment.get('name', ''))
                    amount_var.set(str(payment.get('amount', 0.0)))
            
            # Load savings rate
            savings_rate = profile_data.get('preferred_savings_rate', 0.15) * 100  # Convert to percentage
            self.profile_savings_rate_var.set(str(savings_rate))
            
            # Set current profile
            self.current_profile = profile_data
            self.current_profile_var.set(profile_data.get('profile_name', 'Unknown Profile'))
            
            messagebox.showinfo("Success", f"Profile loaded: {profile_data.get('profile_name', 'Unknown')}")
            
        except Exception as e:
            messagebox.showerror("Error", f"Failed to load profile data: {e}")
    
    def create_profile_dialog(self):
        """Clear form for creating a new profile"""
        self.clear_profile_form()
        self.current_profile = None
        self.current_profile_var.set("Creating New Profile")
        messagebox.showinfo("Info", "Form cleared for new profile creation. Fill in the details and click 'Save Profile'.")
    
    def apply_profile_to_form(self):
        """Apply current profile data to the main input form"""
        if not self.current_profile:
            messagebox.showwarning("Warning", "No profile loaded. Please load a profile first.")
            return
        
        try:
            # Apply to main form
            self.name_var.set(self.current_profile.get('name', ''))
            self.age_var.set(str(self.current_profile.get('age', '')))
            self.location_var.set(self.current_profile.get('location', ''))
            self.household_size_var.set(str(self.current_profile.get('household_size', '')))
            self.bedrooms_var.set(str(self.current_profile.get('bedrooms', '')))
            self.bathrooms_var.set(str(self.current_profile.get('bathrooms', '')))
            
            # Apply utilities
            utilities = self.current_profile.get('default_utilities', {})
            self.water_var.set(str(utilities.get('water', 50.0)))
            self.electricity_var.set(str(utilities.get('electricity', 100.0)))
            self.phone_var.set(str(utilities.get('phone', 80.0)))
            self.other_utilities_var.set(str(utilities.get('other', 30.0)))
            
            # Apply debt
            debt = self.current_profile.get('default_debt', {})
            self.total_debt_var.set(str(debt.get('total_debt', 0.0)))
            self.debt_payment_var.set(str(debt.get('monthly_payment', 0.0)))
            self.debt_type_var.set(debt.get('debt_type', 'No Debt'))
            self.interest_rate_var.set(str(debt.get('interest_rate', 0.0)))
            
            # Apply monthly payments
            monthly_payments = self.current_profile.get('default_monthly_payments', [])
            payment_vars = [
                (self.payment1_name_var, self.payment1_amount_var),
                (self.payment2_name_var, self.payment2_amount_var),
                (self.payment3_name_var, self.payment3_amount_var),
                (self.payment4_name_var, self.payment4_amount_var)
            ]
            
            # Clear all payment fields first
            for name_var, amount_var in payment_vars:
                name_var.set("")
                amount_var.set("0.0")
            
            # Populate with profile payments
            for i, payment in enumerate(monthly_payments[:4]):
                if i < len(payment_vars):
                    name_var, amount_var = payment_vars[i]
                    name_var.set(payment.get('name', ''))
                    amount_var.set(str(payment.get('amount', 0.0)))
            
            # Calculate totals
            self.calculate_totals()
            
            messagebox.showinfo("Success", f"Profile data applied to input form: {self.current_profile.get('profile_name', 'Unknown')}")
            
            # Switch to input tab
            self.notebook.select(0)
            
        except Exception as e:
            messagebox.showerror("Error", f"Failed to apply profile to form: {e}")
    
    def save_profile(self):
        """Save a new profile"""
        try:
            from models import UserProfile, UtilitiesData, DebtData, MonthlyPayment
            
            # Validate required fields
            profile_name = self.profile_name_var.get().strip()
            if not profile_name:
                messagebox.showerror("Error", "Profile name is required.")
                return
            
            # Create profile data
            utilities_data = UtilitiesData(
                water=float(self.profile_water_var.get() or 50.0),
                electricity=float(self.profile_electricity_var.get() or 100.0),
                phone=float(self.profile_phone_var.get() or 80.0),
                other=float(self.profile_other_utilities_var.get() or 30.0)
            )
            
            debt_data = DebtData(
                total_debt=float(self.profile_total_debt_var.get() or 0.0),
                monthly_payment=float(self.profile_debt_payment_var.get() or 0.0),
                debt_type=self.profile_debt_type_var.get() or "No Debt",
                interest_rate=float(self.profile_interest_rate_var.get() or 0.0)
            )
            
            monthly_payments = []
            for name_var, amount_var in self.profile_payment_vars:
                name = name_var.get().strip()
                amount = amount_var.get().strip()
                if name and amount:
                    monthly_payments.append(MonthlyPayment(
                        name=name,
                        amount=float(amount)
                    ))
            
            profile_data = {
                'profile_name': profile_name,
                'name': self.profile_user_name_var.get().strip(),
                'age': int(self.profile_age_var.get() or 30),
                'location': self.profile_location_var.get().strip(),
                'household_size': int(self.profile_household_size_var.get() or 1),
                'bedrooms': int(self.profile_bedrooms_var.get() or 1),
                'bathrooms': float(self.profile_bathrooms_var.get() or 1.0),
                'default_utilities': utilities_data.model_dump(),
                'default_debt': debt_data.model_dump(),
                'default_monthly_payments': [p.model_dump() for p in monthly_payments],
                'preferred_savings_rate': float(self.profile_savings_rate_var.get() or 15.0) / 100.0,  # Convert to decimal
                'created_at': datetime.now().isoformat(),
                'updated_at': datetime.now().isoformat()
            }
            
            # Save to database
            profile_id = f"profile_{uuid.uuid4().hex[:12]}"
            success = db.create_profile(profile_id, profile_data)
            
            if success:
                profile_data['profile_id'] = profile_id
                self.current_profile = profile_data
                self.current_profile_var.set(profile_name)
                messagebox.showinfo("Success", f"Profile saved successfully: {profile_name}")
            else:
                messagebox.showerror("Error", "Failed to save profile.")
                
        except Exception as e:
            messagebox.showerror("Error", f"Failed to save profile: {e}")
    
    def update_profile(self):
        """Update the current profile"""
        if not self.current_profile:
            messagebox.showwarning("Warning", "No profile loaded. Please load a profile first.")
            return
        
        try:
            # Update the profile data with current form values
            profile_data = self.current_profile.copy()
            
            # Update fields
            profile_data['profile_name'] = self.profile_name_var.get().strip()
            profile_data['name'] = self.profile_user_name_var.get().strip()
            profile_data['age'] = int(self.profile_age_var.get() or 30)
            profile_data['location'] = self.profile_location_var.get().strip()
            profile_data['household_size'] = int(self.profile_household_size_var.get() or 1)
            profile_data['bedrooms'] = int(self.profile_bedrooms_var.get() or 1)
            profile_data['bathrooms'] = float(self.profile_bathrooms_var.get() or 1.0)
            
            # Update utilities
            profile_data['default_utilities'] = {
                'water': float(self.profile_water_var.get() or 50.0),
                'electricity': float(self.profile_electricity_var.get() or 100.0),
                'phone': float(self.profile_phone_var.get() or 80.0),
                'other': float(self.profile_other_utilities_var.get() or 30.0)
            }
            
            # Update debt
            profile_data['default_debt'] = {
                'total_debt': float(self.profile_total_debt_var.get() or 0.0),
                'monthly_payment': float(self.profile_debt_payment_var.get() or 0.0),
                'debt_type': self.profile_debt_type_var.get() or "No Debt",
                'interest_rate': float(self.profile_interest_rate_var.get() or 0.0)
            }
            
            # Update monthly payments
            monthly_payments = []
            for name_var, amount_var in self.profile_payment_vars:
                name = name_var.get().strip()
                amount = amount_var.get().strip()
                if name and amount:
                    monthly_payments.append({
                        'name': name,
                        'amount': float(amount)
                    })
            profile_data['default_monthly_payments'] = monthly_payments
            
            # Update savings rate
            profile_data['preferred_savings_rate'] = float(self.profile_savings_rate_var.get() or 15.0) / 100.0
            profile_data['updated_at'] = datetime.now().isoformat()
            
            # Save to database
            success = db.update_profile(self.current_profile['profile_id'], profile_data)
            
            if success:
                self.current_profile = profile_data
                self.current_profile_var.set(profile_data['profile_name'])
                messagebox.showinfo("Success", f"Profile updated successfully: {profile_data['profile_name']}")
            else:
                messagebox.showerror("Error", "Failed to update profile.")
                
        except Exception as e:
            messagebox.showerror("Error", f"Failed to update profile: {e}")
    
    def delete_profile_dialog(self):
        """Show confirmation dialog for profile deletion"""
        if not self.current_profile:
            messagebox.showwarning("Warning", "No profile loaded. Please load a profile first.")
            return
        
        profile_name = self.current_profile.get('profile_name', 'Unknown')
        if messagebox.askyesno("Confirm Delete", f"Are you sure you want to delete the profile '{profile_name}'?\n\nThis action cannot be undone!"):
            try:
                success = db.delete_profile(self.current_profile['profile_id'])
                if success:
                    self.clear_profile_form()
                    self.current_profile = None
                    self.current_profile_var.set("No Profile Selected")
                    messagebox.showinfo("Success", f"Profile deleted: {profile_name}")
                else:
                    messagebox.showerror("Error", "Failed to delete profile.")
            except Exception as e:
                messagebox.showerror("Error", f"Failed to delete profile: {e}")
    
    def clear_profile_form(self):
        """Clear all profile form fields"""
        self.profile_name_var.set("")
        self.profile_user_name_var.set("")
        self.profile_age_var.set("")
        self.profile_location_var.set("")
        self.profile_household_size_var.set("")
        self.profile_bedrooms_var.set("")
        self.profile_bathrooms_var.set("")
        self.profile_water_var.set("50.0")
        self.profile_electricity_var.set("100.0")
        self.profile_phone_var.set("80.0")
        self.profile_other_utilities_var.set("30.0")
        self.profile_total_debt_var.set("0.0")
        self.profile_debt_payment_var.set("0.0")
        self.profile_debt_type_var.set("No Debt")
        self.profile_interest_rate_var.set("0.0")
        self.profile_savings_rate_var.set("15.0")
        
        # Clear monthly payments
        for name_var, amount_var in self.profile_payment_vars:
            name_var.set("")
            amount_var.set("0.0")

def main():
    root = tk.Tk()
    app = BudgetAppGUI(root)
    root.mainloop()

if __name__ == "__main__":
    main()
