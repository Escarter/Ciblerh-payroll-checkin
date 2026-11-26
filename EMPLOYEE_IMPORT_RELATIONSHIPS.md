# Employee Import - Relationship Constraints & Logic

## **Entity Relationships Overview**

```
Company (1) ──── (M) Department (1) ──── (M) Service (1) ──── (M) Employee
     │                     │                     │
     ├── hasMany           ├── belongsTo         ├── belongsTo
     │                     │                     │
     └── hasMany           └── hasMany           └── belongsTo
       (Services)            (Services)            (Employees)
```

## **Database Schema Constraints**

### **Services Table**
- `company_id` (FK to companies.id) - Direct relationship
- `department_id` (FK to departments.id) - Direct relationship
- **Constraint**: Service must belong to a Department that belongs to the same Company

### **Departments Table**
- `company_id` (FK to companies.id)
- **Constraint**: Department must belong to a Company

### **Users Table** (Employees)
- `company_id` (FK to companies.id)
- `department_id` (FK to departments.id)
- `service_id` (FK to services.id)
- **Constraint**: All three relationships must be consistent

## **Import Logic - Relationship Preservation**

### **Priority & Fallback System**
1. **CSV Values Priority**: Try to use department/service names from CSV first
2. **Context Fallback**: If CSV values not found, use context values (no error)
3. **Error Only When Both Fail**: Only throw error if neither CSV nor context available

### **Relationship Constraint Enforcement**

#### **Department Resolution**
```php
// Always constrained to the import company
Department::where('company_id', $this->company->id)
```

#### **Service Resolution**
```php
// Always constrained to resolved department
Service::where('department_id', $resolvedDepartmentId)
```

#### **Employee Assignment**
```php
// All relationships must be consistent
User::create([
    'company_id' => $this->company->id,           // Always provided
    'department_id' => $departmentResult['department']->id,  // Resolved
    'service_id' => $serviceResult['service']->id,           // Resolved
]);
```

## **Critical Relationship Rules**

### **Rule 1: Company Consistency**
- All departments must belong to the import company
- All services must belong to the import company (direct relationship)
- All employees must belong to the import company

### **Rule 2: Department-Service Consistency**
- Services must belong to departments that exist within the same company
- When department is resolved from CSV, services are constrained to that department
- When falling back to context department, services are constrained to context department

### **Rule 3: Transitive Integrity**
```
Employee → Service → Department → Company
Employee → Department → Company
Employee → Company
```
All paths must lead to the same Company entity.

## **Import Scenarios & Relationship Handling**

### **Scenario 1: CSV Department & Service Both Found**
```
CSV: "Operations", "Field Operations"
Result: Department[Operations] ← Service[Field Operations] ← Employee
```

### **Scenario 2: CSV Department Found, Service Not Found → Context Fallback**
```
CSV: "Operations", "NonExistentService"
Context: service = "Project Management"
Result: Department[Operations] ← Service[Project Management] ← Employee
```

### **Scenario 3: CSV Department Not Found → Context Fallback**
```
CSV: "NonExistentDept", "SomeService"
Context: department = "Operations"
Result: Department[Operations] ← Service[SomeService] ← Employee
```

### **Scenario 4: Neither CSV nor Context → Error**
```
CSV: "NonExistentDept", "NonExistentService"
Context: department = null, service = null
Result: ERROR - Department required
```

## **Data Integrity Guarantees**

1. **Company Consistency**: All imported employees belong to the specified company
2. **Department Validity**: All departments exist and belong to the company
3. **Service Validity**: All services exist and belong to valid departments
4. **Relationship Integrity**: No orphaned relationships or inconsistent hierarchies

## **Edge Cases Handled**

1. **Cross-Company Data**: Services from other companies cannot be assigned
2. **Wrong Department Services**: Services from wrong departments cannot be assigned
3. **Context Validation**: Context objects are validated before use
4. **Auto-Creation**: When enabled, maintains all relationship constraints during creation

