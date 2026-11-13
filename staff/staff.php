<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff - Ardor Property Group</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #0b1736;
            --sidebar-hover: #15244b;
            --sidebar-active: #1e305e;
            --accent-orange: #d97706;
            --accent-orange-light: #fef3c7;
            --main-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --btn-primary: #0b1736;
            --btn-primary-hover: #1d305e;
            --status-active-bg: #dcfce7;
            --status-active-text: #15803d;
            --status-pending-bg: #fef3c7;
            --status-pending-text: #b45309;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--main-bg);
            color: var(--text-main);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar Styling */
        aside {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: #94a3b8;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 1px solid #1e293b;
            flex-shrink: 0;
        }

        .sidebar-top {
            padding: 20px;
        }

        .brand-container {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .brand-logo {
            background: var(--accent-orange);
            color: white;
            font-weight: bold;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 14px;
        }

        .brand-name {
            color: white;
            font-weight: 600;
            font-size: 15px;
        }

        .brand-sub {
            font-size: 10px;
            color: #64748b;
            letter-spacing: 0.05em;
        }

        .workspace-box {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 10px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .workspace-title {
            color: white;
            font-size: 13px;
            font-weight: 500;
        }

        .workspace-desc {
            font-size: 11px;
            color: #64748b;
        }

        .nav-section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            margin-bottom: 10px;
            padding-left: 10px;
            font-weight: 600;
        }

        .nav-list {
            list-style: none;
            margin-bottom: 24px;
        }

        .nav-item {
            margin-bottom: 4px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.2s;
        }

        .nav-link:hover {
            background-color: var(--sidebar-hover);
            color: white;
        }

        .nav-link.active {
            background-color: var(--sidebar-active);
            color: white;
            font-weight: 500;
        }

        .nav-link span {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-bottom {
            padding: 20px;
        }

        .help-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
            color: white;
            font-size: 12px;
        }

        /* Main Layout */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        header.top-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 12px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .breadcrumb {
            font-size: 13px;
            color: var(--text-muted);
        }

        .breadcrumb span {
            color: var(--text-main);
            font-weight: 500;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .search-box-top {
            position: relative;
        }

        .search-box-top input {
            padding: 6px 12px 6px 32px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            font-size: 13px;
            width: 220px;
            outline: none;
            background: #f8fafc;
        }

        .search-box-top i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 12px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: #cbd5e1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 12px;
            color: #334155;
        }

        /* Dashboard Body */
        .content-body {
            padding: 28px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .pre-heading {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .page-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .page-subtitle {
            font-size: 13px;
            color: var(--text-muted);
        }

        .btn-action-primary {
            background: var(--btn-primary);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action-primary:hover {
            background: var(--btn-primary-hover);
        }

        /* Stat Cards */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .metric-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .metric-title {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 8px;
        }

        .metric-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .metric-footer {
            font-size: 11px;
            color: var(--text-muted);
        }

        .metric-icon-box {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .metric-value-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-top: 8px;
        }

        .metric-trend {
            font-size: 12px;
            font-weight: 600;
            color: var(--status-active-text);
        }

        /* Card Section */
        .card-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
        }

        .card-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        /* Table Styling */
        .table-search {
            position: relative;
        }

        .table-search input {
            padding: 6px 12px 6px 30px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 12px;
            width: 220px;
            outline: none;
        }

        .table-search i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            background: #f8fafc;
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .staff-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .staff-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
            background: #f1f5f9;
            color: #334155;
        }

        .staff-info .staff-name {
            font-weight: 600;
            color: var(--text-main);
            font-size: 13px;
        }

        .staff-info .staff-email {
            font-size: 11px;
            color: var(--text-muted);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--status-active-bg);
            color: var(--status-active-text);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            background: var(--status-active-text);
            border-radius: 50%;
        }

        .action-dots {
            color: var(--text-muted);
            cursor: pointer;
            border: none;
            background: none;
            font-size: 14px;
        }

        .kbd-shortcut {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 10px;
            color: var(--text-muted);
            background: #ffffff;
            border: 1px solid var(--border-color);
            padding: 1px 5px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside>
        <div class="sidebar-top">
            <div class="brand-container">
                <div class="brand-logo"><i class="fa-solid fa-chevron-left" style="font-size: 12px;"></i></div>
                <div>
                    <div class="brand-name">Ardor</div>
                    <div class="brand-sub">PROPERTY GROUP</div>
                </div>
            </div>

            <div class="workspace-box">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="brand-logo" style="padding: 6px 8px; font-size: 12px;">AP</div>
                    <div>
                        <div class="workspace-title">Ardor Properties</div>
                        <div class="workspace-desc">Enterprise workspace</div>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-down" style="font-size: 11px; color: #94a3b8;"></i>
            </div>

            <div class="nav-section-title">Workspace</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <span><i class="fa-solid fa-border-all"></i> Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="approvals.php" class="nav-link">
                        <span><i class="fa-regular fa-circle-check"></i> Approvals</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="properties.php" class="nav-link">
                        <span><i class="fa-regular fa-building"></i> Properties</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="bidding.php" class="nav-link">
                        <span><i class="fa-solid fa-gavel"></i> Bidding</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="staff.php" class="nav-link active">
                        <span><i class="fa-solid fa-user-group"></i> Staff</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="buyers.php" class="nav-link">
                        <span><i class="fa-regular fa-user"></i> Buyers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link">
                        <span><i class="fa-solid fa-chart-simple"></i> Reports</span>
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">System</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="notification.php" class="nav-link">
                        <span><i class="fa-regular fa-bell"></i> Notifications</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="roles_sec.php" class="nav-link">
                        <span><i class="fa-solid fa-shield-halved"></i> Roles & security</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="sidebar-bottom">
            <div class="help-box">
                <div style="color: var(--accent-orange);"><i class="fa-regular fa-file-lines"></i></div>
                <div style="font-weight: 600;">Need assistance?</div>
                <div style="color: #94a3b8; font-size: 11px;">Browse guides or contact support.</div>
                <a href="#" style="color: var(--accent-orange); text-decoration: none; font-weight: 600; font-size: 11px; margin-top: 4px;">Open help center &rarr;</a>
            </div>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <main class="main-wrapper">
        
        <!-- Top Header -->
        <header class="top-header">
            <div class="breadcrumb">
                Ardor Properties &nbsp;<i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>&nbsp; <span>Staff</span>
            </div>

            <div class="header-right">
                <div class="search-box-top">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search anything">
                    <span class="kbd-shortcut">⌘ K</span>
                </div>

                <div class="user-avatar">EM</div>
            </div>
        </header>

        <!-- Content Body -->
        <div class="content-body">
            
            <!-- Page Header Row -->
            <div class="pre-heading" style="color: var(--accent-orange);">MANAGEMENT</div>
            <div class="page-header-row">
                <div>
                    <h1 class="page-title">Staff</h1>
                    <p class="page-subtitle">Manage staff roles, permissions, profiles, and approval history.</p>
                </div>
                <button class="btn-action-primary">
                    <i class="fa-solid fa-plus"></i> Invite staff
                </button>
            </div>

            <!-- 3 Metric Cards (Matching dashboard metric card layout & icon boxes) -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div>
                        <div class="metric-title">Active staff</div>
                        <div class="metric-value-row">
                            <div class="metric-value" style="margin-bottom: 0;">42</div>
                            <span class="metric-trend" style="color: var(--status-active-text);">+4 this month</span>
                        </div>
                    </div>
                    <div class="metric-icon-box" style="background: #f1f5f9; color: #334155;">
                        <i class="fa-solid fa-user-group"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div>
                        <div class="metric-title">Administrators</div>
                        <div class="metric-value-row">
                            <div class="metric-value" style="margin-bottom: 0;">6</div>
                            <span class="metric-trend" style="color: var(--status-active-text);">2 on duty</span>
                        </div>
                    </div>
                    <div class="metric-icon-box" style="background: var(--accent-orange-light); color: var(--accent-orange);">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div>
                        <div class="metric-title">Reviews this month</div>
                        <div class="metric-value-row">
                            <div class="metric-value" style="margin-bottom: 0;">318</div>
                            <span class="metric-trend" style="color: var(--status-active-text);">+18.4%</span>
                        </div>
                    </div>
                    <div class="metric-icon-box" style="background: var(--status-active-bg); color: var(--status-active-text);">
                        <i class="fa-solid fa-chart-simple"></i>
                    </div>
                </div>
            </div>

            <!-- Staff Overview Table Container -->
            <div class="card-container">
                <div class="card-header-flex">
                    <div>
                        <div style="font-size: 15px; font-weight: 600;">Staff overview</div>
                        <div style="font-size: 12px; color: var(--text-muted);">Recent records and activity</div>
                    </div>
                    <div class="table-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search staff">
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Approvals</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="staff-cell">
                                    <div class="staff-avatar">AM</div>
                                    <div class="staff-info">
                                        <div class="staff-name">Ava Mitchell</div>
                                        <div class="staff-email">ID: STF-1042</div>
                                    </div>
                                </div>
                            </td>
                            <td style="color: var(--text-muted);">Senior Property Officer</td>
                            <td style="color: var(--text-muted);">ava@ardor.com</td>
                            <td style="font-weight: 600;">48</td>
                            <td>
                                <span class="status-badge">
                                    <span class="status-dot"></span> Active
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <button class="action-dots"><i class="fa-solid fa-ellipsis"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

</body>
</html>