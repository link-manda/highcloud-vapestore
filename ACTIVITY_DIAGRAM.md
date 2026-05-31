# HighCloud VapeStore - Activity Diagrams (State-Swimlane Mapping)

This document outlines the operational workflows using Mermaid.js `stateDiagram-v2` syntax. We utilize **Composite States** to act as "Tables" or "Swimlanes", perfectly dividing responsibilities between the User and the System while maintaining the formal state diagram aesthetics.

## 1. Stock In Process (Barang Masuk)

```mermaid
stateDiagram-v2
    [*] --> NavigateToMenu

    %% Swimlane: Staff / Admin
    state "Staff / Admin Input" as Staff {
        NavigateToMenu
        InputData
        FixData
    }

    %% Swimlane: System Logic
    state "System / Backend" as System {
        ValidateData
        CheckPO
        UpdatePO
        SaveRecord
        UpdateStock
        Notify
    }

    %% Transitions
    NavigateToMenu --> InputData
    InputData --> ValidateData
    
    state ValidateData <<choice>>
    ValidateData --> FixData: Invalid
    FixData --> InputData
    
    ValidateData --> CheckPO: Valid
    
    state CheckPO <<choice>>
    CheckPO --> UpdatePO: Has PO
    CheckPO --> SaveRecord: No PO
    
    UpdatePO --> SaveRecord
    SaveRecord --> UpdateStock
    UpdateStock --> Notify
    Notify --> [*]
```

---

## 2. Stock Out Process (Barang Keluar)

```mermaid
stateDiagram-v2
    [*] --> SelectItems

    %% Swimlane: User
    state "Branch Staff" as Staff {
        SelectItems
        InputDetails
        ReadjustItems
    }

    %% Swimlane: System
    state "Inventory System" as System {
        VerifyStock
        ShowError
        SaveTransaction
        DecrementStock
    }

    %% Transitions
    SelectItems --> VerifyStock
    
    state VerifyStock <<choice>>
    VerifyStock --> ShowError: Insufficient Stock
    ShowError --> ReadjustItems
    ReadjustItems --> SelectItems
    
    VerifyStock --> InputDetails: Stock Available
    InputDetails --> SaveTransaction
    SaveTransaction --> DecrementStock
    DecrementStock --> [*]
```

---

## 3. Stock Transfer (Inter-branch)

```mermaid
stateDiagram-v2
    [*] --> SelectBranches

    %% Swimlane: User
    state "Administrator" as Admin {
        SelectBranches
        SelectProducts
        ReviewTransfer
    }

    %% Swimlane: System
    state "System Logic" as System {
        VerifySourceStock
        ShowWarning
        ProcessAtomicTransaction
    }

    %% Transitions
    SelectBranches --> SelectProducts
    SelectProducts --> VerifySourceStock
    
    state VerifySourceStock <<choice>>
    VerifySourceStock --> ShowWarning: No Stock at Source
    ShowWarning --> ReviewTransfer
    ReviewTransfer --> SelectProducts
    
    VerifySourceStock --> ProcessAtomicTransaction: Stock OK
    ProcessAtomicTransaction --> [*]
```

---

## 4. Stock Opname (Penyesuaian Stok)

```mermaid
stateDiagram-v2
    [*] --> SelectBranch

    %% Swimlane: User
    state "Audit Staff" as Staff {
        SelectBranch
        InputPhysicalCount
        InputReason
    }

    %% Swimlane: System
    state "System Audit" as System {
        LoadSystemStock
        CompareStock
        SetBalanced
        SetDiscrepancy
        UpdateSystemStock
    }

    %% Transitions
    SelectBranch --> LoadSystemStock
    LoadSystemStock --> InputPhysicalCount
    InputPhysicalCount --> CompareStock
    
    state CompareStock <<choice>>
    CompareStock --> SetBalanced: Match
    CompareStock --> SetDiscrepancy: Mismatch
    
    SetDiscrepancy --> InputReason
    InputReason --> UpdateSystemStock
    
    SetBalanced --> [*]
    UpdateSystemStock --> [*]
```

---

## 5. Purchase Order (Pemesanan Barang)

```mermaid
stateDiagram-v2
    [*] --> CreateDraft

    %% Swimlane: Requester
    state "Requester (Admin)" as Requester {
        CreateDraft
        SetPending
    }

    %% Swimlane: Approver
    state "Approver (Senior Admin)" as Approver {
        ReviewPO
    }

    %% Swimlane: System
    state "PO System" as System {
        CancelPO
        GenerateOrder
        UpdateStatus
    }

    %% Transitions
    CreateDraft --> SetPending
    SetPending --> ReviewPO
    
    state ReviewPO <<choice>>
    ReviewPO --> CancelPO: Rejected
    ReviewPO --> GenerateOrder: Approved
    
    GenerateOrder --> UpdateStatus
    UpdateStatus --> [*]
    CancelPO --> [*]
```
