# HighCloud VapeStore - Sequence Diagrams

This document outlines the core business processes of the HighCloud VapeStore Inventory System using Mermaid.js Sequence Diagrams. It details the exact interactions between Actors (Users), the User Interface (Filament), Backend Models/Services, and the Database.

## 1. Stock In Process (Barang Masuk)

This diagram illustrates the process of recording incoming stock from suppliers, including optional integration with existing Purchase Orders.

```mermaid
sequenceDiagram
    autonumber
    actor Admin
    participant UI as Filament Admin Panel
    participant Model as BarangMasuk Model
    participant DB as Database (MySQL)

    Admin->>UI: Input Receiving Data (Supplier, Branch, Items, Optional PO)
    UI->>Model: validateData()
    
    opt Has Purchase Order Reference
        Model->>DB: Check PO Status (purchase_orders)
        DB-->>Model: Return PO Details
    end

    rect rgb(240, 240, 240)
        Note over Model, DB: Database Transaction
        Model->>DB: Begin Transaction
        Model->>DB: Insert into barang_masuks & barang_masuk_details
        Model->>DB: Update stok_cabangs (Increment Stock)
        
        opt Has Purchase Order Reference
            Model->>DB: Update PO Status (e.g., 'Received')
        end
        
        Model->>DB: Commit Transaction
        DB-->>UI: Success Confirmation
    end
    UI-->>Admin: Show Success Notification
```

### Breakdown: Stock In
1. **Input & Validation**: Admin inputs received items.
2. **PO Integration**: If linked to a PO, the system fetches it to sync statuses.
3. **Atomic Transaction**: Ensures that the `barang_masuks` record and the `stok_cabangs` increment happen together.
4. **PO Status Sync**: Marks the corresponding Purchase Order as fulfilled if applicable.

---

## 2. Stock Out Process (Barang Keluar)

This diagram illustrates the process of recording a stock-out (sale/usage) through the Filament Admin Panel.

```mermaid
sequenceDiagram
    autonumber
    actor Staff as Branch Staff/Admin
    participant UI as Filament Admin Panel
    participant Model as BarangKeluar Model
    participant DB as Database (MySQL)

    Staff->>UI: Input Transaction Data (Product, Qty, Branch)
    UI->>Model: validate()
    
    rect rgb(240, 240, 240)
        Note over Model, DB: Transaction Logic
        Model->>DB: Check Stock Availability (stok_cabangs)
        DB-->>Model: Return Current Stock
        
        alt Stock Sufficient
            Model->>DB: Start Transaction
            Model->>DB: Create barang_keluars & details
            Model->>DB: Update stok_cabangs (Decrement Stock)
            Model->>DB: Commit Transaction
            DB-->>UI: Success Message
            UI-->>Staff: Display Transaction Confirmed
        else Stock Insufficient
            Model-->>UI: Error: Stock Not Enough
            UI-->>Staff: Display Validation Error
        end
    end
```

### Breakdown: Stock Out
1. **Input Data**: Staff enters product details, quantity, and branch context.
2. **Stock Check**: The system queries `stok_cabangs` to ensure the specific branch has enough stock.
3. **Database Transaction**: If sufficient, stock is decremented and the log is saved atomically. Prevents negative stock.

---

## 3. Stock Transfer Process (Antar Cabang)

This diagram shows how stock is securely moved from one branch to another without data loss.

```mermaid
sequenceDiagram
    autonumber
    actor Admin
    participant UI as Filament Transfer Resource
    participant Model as TransferStok Service
    participant DB as Database

    Admin->>UI: Create Transfer Request (Source, Dest, Items)
    UI->>Model: processTransfer()
    
    Model->>DB: Verify Source Branch Stock
    
    alt Stock Available
        Model->>DB: Begin Transaction
        Model->>DB: Create transfer_stoks record
        Model->>DB: Decrement Source Stock (stok_cabangs)
        Model->>DB: Increment Destination Stock (stok_cabangs)
        Model->>DB: Commit Transaction
        DB-->>UI: Transfer Completed
        UI-->>Admin: Show Success Notification
    else Stock Unavailable
        Model-->>UI: Error: Source Branch Out of Stock
        UI-->>Admin: Show Error Alert
    end
```

### Breakdown: Stock Transfer
1. **Verification**: System ensures the source branch actually holds the stock being transferred.
2. **Double Mutation**: Using a single transaction, the system deducts from the source and adds to the destination, preventing "lost in transit" digital discrepancies.

---

## 4. Stock Opname Process (Penyesuaian Stok)

This diagram details the physical auditing process and how the system rectifies discrepancies.

```mermaid
sequenceDiagram
    autonumber
    actor Auditor as Audit Staff
    participant UI as Filament Opname Resource
    participant Model as StockOpname Model
    participant DB as Database

    Auditor->>UI: Select Branch & Load System Stock
    UI->>DB: Query Current stok_cabangs
    DB-->>UI: Return Digital Records
    
    Auditor->>UI: Input Physical Count Results
    UI->>Model: processOpname()

    rect rgb(240, 240, 240)
        Note over Model, DB: Audit Resolution
        Model->>DB: Begin Transaction
        Model->>DB: Insert stock_opnames & details
        
        alt Physical Count == System Stock
            Model->>DB: Set Status: 'Balanced'
        else Physical Count != System Stock
            Auditor->>UI: Input Reason for Discrepancy
            UI->>Model: appendReason()
            Model->>DB: Update stok_cabangs to match Physical
            Model->>DB: Set Status: 'Adjusted'
        end
        
        Model->>DB: Commit Transaction
        DB-->>UI: Opname Finalized
    end
    UI-->>Auditor: Show Audit Report
```

### Breakdown: Stock Opname
1. **Data Sync**: Auditor loads the system's expected stock values.
2. **Comparison**: The system compares physical counts against system records.
3. **Adjustment**: If a mismatch occurs, a reason must be provided, and the system forcefully updates the database to reflect physical reality, logging the discrepancy.

---

## 5. Purchase Order (Pemesanan Barang)

This diagram covers the workflow of drafting and approving a Purchase Order to a supplier.

```mermaid
sequenceDiagram
    autonumber
    actor Requester as Admin (Requester)
    actor Approver as Senior Admin
    participant UI as Filament PO Resource
    participant Model as PurchaseOrder Model
    participant DB as Database

    %% Drafting Phase
    Requester->>UI: Input Supplier & Desired Items
    UI->>Model: createDraft()
    Model->>DB: Insert purchase_orders (Status: Pending)
    DB-->>UI: Draft Created
    UI-->>Requester: Show Pending PO

    %% Approval Phase
    Approver->>UI: Review Pending PO
    
    alt Approve PO
        Approver->>UI: Click Approve
        UI->>Model: updateStatus('Ordered')
        Model->>DB: Update purchase_orders
        DB-->>UI: Status Updated
        UI-->>Approver: Show Ordered Success
        Note over Model: System sends Email/PDF to Supplier
    else Reject PO
        Approver->>UI: Click Reject
        UI->>Model: updateStatus('Cancelled')
        Model->>DB: Update purchase_orders
        DB-->>UI: Status Cancelled
        UI-->>Approver: Show Cancellation
    end
```

### Breakdown: Purchase Order
1. **Drafting**: An initial PO is created with a 'Pending' status. It does not affect stock.
2. **Approval Hierarchy**: A different (or senior) admin reviews the PO.
3. **Finalization**: Upon approval, the PO is marked 'Ordered' and dispatched to the supplier. This PO will later be referenced in the **Stock In** process.
