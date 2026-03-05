# 🚢 Port Queuing Management System - User Manual

Welcome to the Port Queuing Management System (PQMS). This manual guides you through the daily operations of the system for Customers, Counter Staff, and Administrators.

## 🔗 Quick Access

| Portal | URL | Description |
|--------|-----|-------------|
| **Customer Kiosk** | `http://localhost/qs/customer/` | Where drivers/captains generate tokens. |
| **Admin Panel** | `http://localhost/qs/admin/` | Staff login for processing & management. |
| **Display Board** | `http://localhost/qs/display/` | Large screen view for waiting areas. |
| **Home Page** | `http://localhost/qs/` | Operational landing page. |

---

## 🚛 For Customers (Drivers & Captains)

### 1. Generating a Token
1. Go to the **Customer Kiosk** page.
2. **Select Vessel Type**: Choose between Cargo Ship, RORO, Boat, or General.
3. **Select Service**: The available services (like Berthing, Documentation, or Loading) will appear based on your vessel choice.
4. **Select Priority**:
   - *Regular*: Standard processing.
   - *Perishable/Hazmat/Emergency*: Grants higher priority in the queue.
5. **Vessel Details**:
   - Enter your **Vessel Name** and **Captain Name**.
   - (Optional) Enter mobile number for notifications.
6. Click **Generate Token**.
7. Your token will be printed (or shown on screen). **Keep your token number!**

### 2. Checking Status
- Use the **Check Token Status** link on the kiosk.
- Enter your token number to see your position in the queue and estimated wait time.

---

## 👮 For Counter Staff

### 1. Logging In
1. Go to the **Admin Panel**.
2. Enter your credentials.
   - Default: `admin` / `admin123`

### 2. Processing the Queue
1. Navigate to the **Counter** tab.
2. Select the specific counter/window you are working at (e.g., "Cargo Window 1").
3. Click **Start Session**.
4. **Calling a Token**:
   - Click **Call Next**. The system will automatically pick the highest priority waiting customer compatible with your counter's services.
   - The token number will flash on the **Display Board**.
5. **Serving**:
   - Once the customer arrives, click **Start Serving**.
6. **Finishing**:
   - Click **Complete** when the transaction is done.
   - Click **No Show** if the customer does not appear after repeated calls.

---

## 👨‍💼 For Administrators

### 1. Managing Vessels
Before operations, you should register regular vessels.
1. Go to **Vessels** in the menu.
2. Click **Add New Vessel**.
3. Enter details like *Name*, *Type*, and *Registration Number*.
4. This helps in tracking recurring visits.

### 2. Managing Schedules
Link vessels to their trips.
1. Go to **Schedules**.
2. Click **Schedule New Trip**.
3. Select a registered **Vessel**.
4. Enter **Trip Number** and **Arrival/Departure** times.
5. Update the status (e.g., *Arrived*, *Boarding*) as the operation progresses.

### 3. Managing Users
Create accounts for your staff.
1. Go to **Users**.
2. Click **Add New User**.
3. **Role Types**:
   - *Counter Staff*: Can only process queues.
   - *Admin*: Can manage counters and reports.
   - *Super Admin*: Full access to settings and users.
4. (Optional) Assign a user to a specific counter so they verify into it automatically.

---

## 📺 The Display Board

- Open the **Display Board** URL on a large monitor in the waiting area.
- It refreshes automatically every **3 seconds**.
- **Features**:
   - Shows currently serving tokens.
   - Flashes/Highlights newly called tokens.
   - Displays "Waiting for next customer" when counters are idle.

---

## 🔧 Frequently Asked Questions

**Q: A high priority token appeared but I got a regular one?**
A: Counters are assigned specific services. If you are at a "Documentation" window, you won't get a "Loading" token, even if it's high priority.

**Q: How do I change the text scrolling at the bottom of the screen?**
A: This is currently hardcoded in `display/index.php`. Ask your technical administrator to update it.

**Q: I forgot my password.**
A: Ask a Super Admin to reset it via the **Users** menu. If the Super Admin forgets their password, a database reset script is required.

