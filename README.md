# School ERP System - Complete Testing & Setup Guide

A comprehensive **School Enterprise Resource Planning (ERP)** system with three role-based portals:
- **Admin Panel** - School administration and system management
- **Teacher Panel** - Academic management, assignments, and grading
- **Student Panel** - Academic tracking, attendance, and fee management

All three panels share a unified database and schema for complete integration.

---

## 🚀 Quick Start (5 minutes)

### Prerequisites
- PHP 7.4+ (Laragon/XAMPP/WAMP)
- MySQL 5.7+
- Modern web browser

### Steps to Run

1. **Place files in web root**
   ```
   C:\laragon\www\GitHub\school-erp\
   (or your PHP server root)
   ```

2. **Start Services**
   - Start MySQL
   - Start Apache/PHP server

3. **Run Setup**
   - Visit: `http://localhost/school-erp/setup.php`
   - Tables will be created automatically

4. **Login & Test**
   - Visit: `http://localhost/school-erp/login.php`
   - Use credentials below

---

## 🔐 Default Login Credentials

| Role | Username | Password | Dashboard |
|------|----------|----------|-----------|
| **Admin** | admin | admin123 | /admin/dashboard.php |
| **Teacher** | teacher1 | teacher123 | /teacher/dashboard.php |
| **Student** | student1 | student123 | /student/dashboard.php |

---

## ✅ Complete Testing Checklist

### **Phase 1: Authentication & Security** (5 mins)

#### Login Functionality
- [ ] **Admin Login Works**
  - Login: `admin` / `admin123`
  - Verify: redirects to `/admin/dashboard.php`
  - See: Admin interface loads correctly

- [ ] **Teacher Login Works**
  - Login: `teacher1` / `teacher123`
  - Verify: redirects to `/teacher/dashboard.php`
  - See: Modern Material Design sidebar

- [ ] **Student Login Works**
  - Login: `student1` / `student123`
  - Verify: redirects to `/student/dashboard.php`
  - See: Student dashboard with statistics

#### Security Checks
- [ ] **Unauthorized Access Blocked**
  - Try accessing `/admin/` without login → redirects to login ✓
  - Try accessing `/teacher/` without login → redirects to login ✓
  - Try accessing `/student/` without login → redirects to login ✓

- [ ] **Wrong Credentials Rejected**
  - Try: `admin` / `wrongpassword` → "Invalid password" shown ✓
  - Try: `nonexistent` / `admin123` → "User not found" shown ✓

- [ ] **Logout Works**
  - Click logout button in sidebar
  - Redirects to login page ✓
  - Session is cleared ✓

---

### **Phase 2: Student Panel Testing** (15 mins)

#### Dashboard Page
- [ ] **Page Loads** without errors
- [ ] **Sidebar Displays** correctly with:
  - [ ] Student Portal logo
  - [ ] Student name display
  - [ ] Navigation menu items
  - [ ] Logout button
- [ ] **Statistics Cards Show:**
  - [ ] Attendance count
  - [ ] Average marks
  - [ ] Pending fees (₹ amount)
  - [ ] Leave applications count
- [ ] **Quick Actions Section:**
  - [ ] View Attendance Records link
  - [ ] Check Your Marks link
  - [ ] View Fee Status link
  - [ ] Apply for Leave link
  - [ ] Edit Profile link
- [ ] **All Links Work** without errors

#### Attendance Page (`/student/attendance.php`)
- [ ] Page loads without errors
- [ ] Sidebar displays correctly
- [ ] Attendance records show (if any exist)
- [ ] Back navigation works

#### Marks Page (`/student/marks.php`)
- [ ] Page loads without errors
- [ ] Student marks display
- [ ] Average calculation visible
- [ ] Subject-wise marks show

#### Fees Page (`/student/fees.php`)
- [ ] Page loads without errors
- [ ] Fee records display
- [ ] Paid/Pending status shows
- [ ] Total amount calculations correct
- [ ] Can filter by status

#### Leave Application Page (`/student/leave.php`)
- [ ] Page loads without errors
- [ ] Can view submitted leaves
- [ ] Leave form works (if applicable)
- [ ] Status tracking visible

#### Profile Page (`/student/profile.php`)
- [ ] Page loads without errors
- [ ] Student information displays
- [ ] All profile fields readable
- [ ] Edit functionality (if enabled)

---

### **Phase 3: Teacher Panel Testing** (15 mins)

#### Dashboard Page
- [ ] Page loads with **Material Design UI**
- [ ] **Sidebar displays:**
  - [ ] Teacher Portal branding
  - [ ] Navigation items (Dashboard, Assignments, Attendance, Grades, Schedule, Students, Profile)
  - [ ] Faculty Recognition widget
  - [ ] Logout button
- [ ] **Dashboard Statistics:**
  - [ ] Total Students count
  - [ ] Total Assignments count
  - [ ] Graded Submissions count
  - [ ] Today's Attendance count
- [ ] **Charts/Lists Display** (if applicable)
- [ ] No JavaScript errors in console

#### Navigation Items
- [ ] **Dashboard** - Shows statistics
- [ ] **Assignments** - Assignment management
- [ ] **Attendance** - Attendance tracking
- [ ] **Grades** - Grade management
- [ ] **Schedule** - Class schedule
- [ ] **Students** - Student list
- [ ] **Profile** - Teacher profile

#### Active Navigation Highlighting
- [ ] Current page menu item highlighted
- [ ] Highlight changes when navigating

#### Logout Functionality
- [ ] Logout button visible
- [ ] Clicking logout redirects to login ✓
- [ ] Session cleared properly ✓

---

### **Phase 4: Admin Panel Testing** (10 mins)

- [ ] Dashboard loads without errors
- [ ] Admin interface displays correctly
- [ ] Sidebar navigation works
- [ ] User management accessible
- [ ] Can manage teachers
- [ ] Can manage students
- [ ] Can manage classes
- [ ] System settings accessible
- [ ] Logout button works

---

### **Phase 5: Database Integrity Check** (5 mins)

**Check via phpMyAdmin** (`http://localhost/phpmyadmin`)

#### Required Tables Exist
- [ ] `users` - Login credentials
- [ ] `students` - Student records
- [ ] `teachers` - Teacher information (if applicable)
- [ ] `classes` - Class information
- [ ] `attendance` - Attendance records
- [ ] `marks` - Student marks/grades
- [ ] `fees` - Fee records
- [ ] `leave_applications` - Leave requests
- [ ] `assignments` - Assignment data
- [ ] `assignment_submissions` - Submission tracking

#### Sample Data Present
- [ ] Default user present: `admin` with role `admin`
- [ ] Default user present: `teacher1` with role `teacher`
- [ ] Default user present: `student1` with role `student`
- [ ] No error records or corrupt data

#### Database Connection
- [ ] Database name: `school_erp` ✓
- [ ] All tables created properly ✓
- [ ] Proper relationships between tables ✓

---

### **Phase 6: UI/UX Consistency Testing** (10 mins)

#### Student Panel Design
- [ ] Modern, clean interface
- [ ] Consistent color scheme across all pages
- [ ] Material Symbols icons display correctly
- [ ] Tailwind CSS styling applied properly
- [ ] Responsive layout (test on mobile)
- [ ] Sidebar color: White with stone-900 text
- [ ] Primary color accents appear throughout
- [ ] Hover effects work on all buttons/links

#### Teacher Panel Design
- [ ] Material Design UI consistent
- [ ] Sidebar matches teacher branding
- [ ] Icons display correctly
- [ ] Cards have proper shadows and borders
- [ ] Color scheme professional
- [ ] Active navigation state visible
- [ ] Responsive on smaller screens

#### Overall Consistency
- [ ] Both panels have similar design language
- [ ] Navigation patterns consistent
- [ ] Button styles uniform
- [ ] Font sizes readable
- [ ] Spacing adequate

---

### **Phase 7: Error Handling & Edge Cases** (10 mins)

- [ ] Invalid login shows error message
- [ ] Database connection error handled gracefully
- [ ] Missing tables don't crash the system
- [ ] Page refresh doesn't lose session
- [ ] Invalid URLs show proper 404 or redirect
- [ ] Large data loads without hanging
- [ ] Navigation between pages smooth
- [ ] No JavaScript console errors

---

### **Phase 8: Performance & Browser Compatibility** (5 mins)

- [ ] Pages load within 2 seconds
- [ ] No memory leaks on repeated navigation
- [ ] Works in Chrome/Firefox/Edge
- [ ] Mobile view responsive
- [ ] Print functionality works (if applicable)
- [ ] All forms submit successfully

---

## 🌐 Important URLs Reference

| Purpose | URL |
|---------|-----|
| Home | `http://localhost/school-erp/index.php` |
| Login | `http://localhost/school-erp/login.php` |
| Setup | `http://localhost/school-erp/setup.php` |
| Admin Dashboard | `http://localhost/school-erp/admin/dashboard.php` |
| Teacher Dashboard | `http://localhost/school-erp/teacher/dashboard.php` |
| Student Dashboard | `http://localhost/school-erp/student/dashboard.php` |
| phpMyAdmin | `http://localhost/phpmyadmin` |

---

## 🛠️ Troubleshooting Guide

### "Error: Database connection failed"
**Solution:**
1. Verify MySQL is running
2. Check credentials in `includes/db_connect.php`
3. Ensure `school_erp` database exists
4. Run `setup.php` to create tables

### "User not found" at login
**Solution:**
1. Run `http://localhost/school-erp/setup.php`
2. Check phpMyAdmin for user records
3. Verify username exactly matches (case-sensitive)

### "Fatal error: Uncaught TypeError in dashboard.php"
**Solution:**
1. Check MySQL connection is active
2. Verify all required tables exist
3. Run setup.php again
4. Check error logs for details

### Sidebar doesn't display correctly
**Solution:**
1. Verify Material Symbols font loads
2. Check Tailwind CSS CDN is accessible
3. Clear browser cache (Ctrl+Shift+Del)
4. Check browser console for errors

### Pages redirect to login immediately
**Solution:**
1. Clear browser cookies
2. Close and reopen browser
3. Check `$_SESSION` variables in PHP code
4. Verify session.start() is called

---

## 📊 Architecture Overview

### Database Structure
```
users (login credentials for all roles)
├── students (student details)
├── teachers (teacher details)
├── classes (class information)
│   ├── attendance
│   ├── marks
│   ├── fees
│   ├── leave_applications
│   └── assignments
```

### File Structure
```
school-erp/
├── includes/
│   ├── db_connect.php (unified DB connection)
│   └── ...
├── admin/ (admin panel)
├── teacher/ (teacher panel)
├── student/ (student panel)
├── assets/ (CSS, JS, fonts)
├── login.php (authentication)
├── setup.php (database initialization)
└── README.md (this file)
```

---

## ✨ Key Features

✅ **Unified Database** - All three panels share the same data
✅ **Role-Based Access Control** - Secure separation of concerns
✅ **Modern UI** - Material Design and Tailwind CSS
✅ **Responsive Design** - Works on desktop and mobile
✅ **Error Handling** - Graceful failure modes
✅ **Auto Database Setup** - One-click initialization
✅ **Session Management** - Secure authentication
✅ **Prepared Statements** - SQL injection protection

---

## 📝 Test Execution Notes

### Before Testing
- [ ] Close all browser tabs and clear cache
- [ ] Restart PHP server and MySQL
- [ ] Note any custom modifications
- [ ] Document baseline performance metrics

### During Testing
- [ ] Take screenshots of any errors
- [ ] Note exact steps to reproduce issues
- [ ] Check browser console for errors
- [ ] Monitor MySQL error logs

### After Testing
- [ ] Document all findings
- [ ] List any broken features
- [ ] Note performance issues
- [ ] Summarize test results

---

## 🎯 Expected Test Results

### ✅ PASS Scenario
All three panels load and function properly:
- Admin can access admin features
- Teacher can access teacher features
- Student can access student features
- All navigation works
- Data displays correctly
- No console errors

### ⚠️ PARTIAL PASS Scenario
Some features work, some need fixes:
- Login works but styles are broken
- Dashboard shows data but some pages error
- Some navigation links broken
- Minor database issues

### ❌ FAIL Scenario
System doesn't work:
- Login fails
- Database connection error
- All pages show errors
- Unauthorized access not blocked
- Data corruption detected

---

## 📞 Quick Support

**Problem?** Check this order:
1. Run `setup.php` again
2. Restart MySQL and PHP
3. Clear browser cache
4. Check error logs
5. Review troubleshooting section above

---

**Status:** ✅ **Ready for Complete Testing**

**Last Updated:** March 30, 2026
**Test Environment:** Laragon PHP Server
**Database:** MySQL 5.7+
**Tested Browsers:** Chrome, Firefox, Edge
