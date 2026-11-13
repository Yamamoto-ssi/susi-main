<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ardor Property Group - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_admin.css">
    <style>
        /* Additional styles for dashboard components not defined in style_admin.css */
        .dashboard-grid-2-1 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        @media (max-width: 1024px) {
            .dashboard-grid-2-1 {
                grid-template-columns: 1fr;
            }
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

        .chart-legend {
            display: flex;
            justify-content: flex-end;
            gap: 16px;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .legend-line {
            width: 12px;
            height: 2px;
            display: inline-block;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 20px;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .activity-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .activity-text {
            font-size: 12px;
            color: var(--text-main);
            line-height: 1.4;
        }

        .activity-time {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .live-banner {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            height: 200px;
            background: #0f172a;
            margin-bottom: 16px;
        }

        .live-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.8;
        }

        .live-banner-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(11, 23, 54, 0.9), transparent);
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .live-pill {
            align-self: flex-start;
            background: #ffffff;
            color: var(--text-main);
            font-size: 10px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
        }

        .live-metrics-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
        }

        .timeline-list {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding-left: 4px;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            position: relative;
        }

        .timeline-time {
            font-size: 12px;
            color: var(--text-muted);
            width: 36px;
            flex-shrink: 0;
        }

        .timeline-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-top: 3px;
            flex-shrink: 0;
        }

        .view-all-link {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-main);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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
                <div class="brand-logo"><i class="fa-solid fa-chevron-left"></i></div>
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
                <i class="fa-solid fa-chevron-down" style="font-size: 11px;"></i>
            </div>

            <div class="nav-section-title">Workspace</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="#" class="nav-link active">
                        <span><i class="fa-solid fa-border-all"></i> Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="approvals.php" class="nav-link">
                        <span><i class="fa-regular fa-circle-check"></i> Approvals</span>
                        <span class="badge-count">12</span>
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
                    <a href="staff.php" class="nav-link">
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
                        <span class="badge-dot"></span>
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
            <div class="help-box" style="flex-direction: column; align-items: flex-start; gap: 6px;">
                <div style="color: var(--accent-orange);"><i class="fa-regular fa-file-lines"></i></div>
                <div style="font-weight: 600;">Need assistance?</div>
                <div style="color: #94a3b8; font-size: 11px;">Browse guides or contact support.</div>
                <a href="#" style="color: var(--accent-orange); text-decoration: none; font-weight: 600; font-size: 11px; margin-top: 4px;">Open help center &rarr;</a>
            </div>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        
        <!-- Top Header -->
        <header class="top-header">
            <div class="breadcrumb">
                Ardor Properties &nbsp;<i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>&nbsp; <span>Dashboard</span>
            </div>

            <div class="header-right">
                <div class="search-box-top">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search anything">
                    <span class="kbd-shortcut">⌘ K</span>
                </div>

                <button class="icon-btn" style="position: relative;">
                    <i class="fa-regular fa-bell"></i>
                    <span class="badge-count" style="position: absolute; top: -5px; right: -5px; padding: 1px 5px; font-size: 9px;">8</span>
                </button>

                <div class="user-avatar">EM</div>
            </div>
        </header>

        <!-- Content Body -->
        <div class="content-body">
            
            <!-- Page Header Row -->
            <div class="page-header-row">
                <div>
                    <div class="pre-heading" style="color: var(--accent-orange);">MONDAY, MAY 26</div>
                    <h1 class="page-title">Good morning, Elena</h1>
                    <p class="page-subtitle">Here's what needs your attention across the portfolio today.</p>
                </div>
                <button class="btn-action-primary">
                    <i class="fa-solid fa-plus"></i> Review new submission
                </button>
            </div>

            <!-- 4 Metric Cards -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div>
                        <div class="metric-title">Registered properties</div>
                        <div class="metric-value-row">
                            <div class="metric-value" style="margin-bottom: 0;">1,248</div>
                            <span class="metric-trend">+8.2%</span>
                        </div>
                    </div>
                    <div class="metric-icon-box" style="background: #f1f5f9; color: #334155;">
                        <i class="fa-solid fa-house"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div>
                        <div class="metric-title">Pending approvals</div>
                        <div class="metric-value-row">
                            <div class="metric-value" style="margin-bottom: 0;">12</div>
                            <span class="metric-trend">4 urgent</span>
                        </div>
                    </div>
                    <div class="metric-icon-box" style="background: var(--accent-orange-light); color: var(--accent-orange);">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div>
                        <div class="metric-title">Active bidding</div>
                        <div class="metric-value-row">
                            <div class="metric-value" style="margin-bottom: 0;">28</div>
                            <span class="metric-trend">+3 today</span>
                        </div>
                    </div>
                    <div class="metric-icon-box" style="background: #f3e8ff; color: #7e22ce;">
                        <i class="fa-solid fa-gavel"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div>
                        <div class="metric-title">Completed sales</div>
                        <div class="metric-value-row">
                            <div class="metric-value" style="margin-bottom: 0;">$8.42M</div>
                            <span class="metric-trend">+12.6%</span>
                        </div>
                    </div>
                    <div class="metric-icon-box" style="background: var(--status-active-bg); color: var(--status-active-text);">
                        <i class="fa-solid fa-chart-simple"></i>
                    </div>
                </div>
            </div>

            <!-- Middle Grid: Portfolio Performance & Recent Activity -->
            <div class="dashboard-grid-2-1">
                
                <!-- Portfolio Performance -->
                <div class="card-container" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div class="card-header-flex">
                            <div>
                                <div class="card-title">Portfolio performance</div>
                                <div class="card-subtitle">Property activity over the last 6 months</div>
                            </div>
                            <div class="btn-action-secondary" style="font-size: 12px; padding: 6px 12px;">
                                Last 6 months <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i>
                            </div>
                        </div>

                        <div class="chart-legend">
                            <div class="legend-item"><span class="legend-line" style="background: var(--sidebar-bg);"></span> Approved</div>
                            <div class="legend-item"><span class="legend-line" style="background: var(--accent-orange);"></span> Sold</div>
                        </div>
                    </div>

                    <!-- Static SVG Chart -->
                    <div style="width: 100%; padding-top: 8px;">
                        <svg viewBox="0 0 650 185" style="width: 100%; height: 200px; overflow: visible;">
                            <g style="font-size: 10px; fill: #64748b;">
                                <text x="0" y="15">80</text>
                                <line x1="25" y1="12" x2="640" y2="12" stroke="#f1f5f9" stroke-width="1"/>
                                
                                <text x="0" y="55">60</text>
                                <line x1="25" y1="52" x2="640" y2="52" stroke="#f1f5f9" stroke-width="1"/>
                                
                                <text x="0" y="95">40</text>
                                <line x1="25" y1="92" x2="640" y2="92" stroke="#f1f5f9" stroke-width="1"/>
                                
                                <text x="0" y="135">20</text>
                                <line x1="25" y1="132" x2="640" y2="132" stroke="#f1f5f9" stroke-width="1"/>

                                <text x="0" y="165">0</text>
                                <line x1="25" y1="162" x2="640" y2="162" stroke="#e2e8f0" stroke-width="1"/>
                            </g>

                            <!-- Approved Line -->
                            <path d="M 35 142 C 90 135, 110 112, 155 116 C 200 120, 230 85, 280 90 C 330 95, 360 70, 410 75 C 460 80, 490 50, 535 58 C 580 65, 600 32, 635 34" fill="none" stroke="#0b1736" stroke-width="2" stroke-linecap="round"/>

                            <!-- Sold Line -->
                            <path d="M 35 156 C 90 150, 110 138, 155 142 C 200 146, 230 118, 280 122 C 330 126, 360 100, 410 105 C 460 110, 490 80, 535 85 C 580 90, 600 65, 635 67" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round"/>

                            <g style="font-size: 10px; fill: #64748b;" text-anchor="middle">
                                <text x="40" y="182">Dec</text>
                                <text x="155" y="182">Jan</text>
                                <text x="275" y="182">Feb</text>
                                <text x="395" y="182">Mar</text>
                                <text x="515" y="182">Apr</text>
                                <text x="630" y="182">May</text>
                            </g>
                        </svg>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card-container" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div class="card-header-flex">
                            <div>
                                <div class="card-title">Recent activity</div>
                                <div class="card-subtitle">Latest team updates</div>
                            </div>
                            <button class="action-dots"><i class="fa-solid fa-ellipsis"></i></button>
                        </div>

                        <div class="activity-list">
                            <div class="activity-item">
                                <div class="activity-avatar" style="background: #f1f5f9; color: #334155;">AM</div>
                                <div>
                                    <div class="activity-text"><strong>Ava Mitchell</strong> approved Horizon Pool Villa</div>
                                    <div class="activity-time">12 min ago</div>
                                </div>
                            </div>

                            <div class="activity-item">
                                <div class="activity-avatar" style="background: var(--accent-orange-light); color: var(--status-pending-text);">JL</div>
                                <div>
                                    <div class="activity-text"><strong>James Lee</strong> requested ownership documents</div>
                                    <div class="activity-time">38 min ago</div>
                                </div>
                            </div>

                            <div class="activity-item">
                                <div class="activity-avatar" style="background: var(--status-active-bg); color: var(--status-active-text);">NS</div>
                                <div>
                                    <div class="activity-text"><strong>Nora Scott</strong> closed bidding for Oakridge Estate</div>
                                    <div class="activity-time">1 hr ago</div>
                                </div>
                            </div>

                            <div class="activity-item">
                                <div class="activity-avatar" style="background: #f3e8ff; color: #7e22ce;">RK</div>
                                <div>
                                    <div class="activity-text"><strong>Ryan Kim</strong> verified a new buyer profile</div>
                                    <div class="activity-time">2 hrs ago</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="#" class="btn-action-secondary" style="justify-content: center; width: 100%; text-decoration: none;">
                        View activity log &rarr;
                    </a>
                </div>
            </div>

            <!-- Approval Queue Table -->
            <div class="card-container" style="margin-bottom: 24px;">
                <div class="card-header-flex">
                    <div>
                        <div class="card-title">Approval queue</div>
                        <div class="card-subtitle">Properties waiting for staff action</div>
                    </div>
                    <a href="#" class="view-all-link">View all <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Owner</th>
                            <th>Asking Price</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="property-cell">
                                    <img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=120&q=80" alt="Cedar Grove Residence" class="property-thumb">
                                    <div class="property-info">
                                        <div class="prop-name">Cedar Grove Residence</div>
                                        <div class="prop-addr">PR-2048 · 84 Cedar Lane, Austin, TX</div>
                                    </div>
                                </div>
                            </td>
                            <td>Olivia Bennett</td>
                            <td>
                                <div class="price-main">$1,280,000</div>
                                <div class="price-sub">Min $1,150,000</div>
                            </td>
                            <td style="color: var(--text-muted);">May 24, 2025</td>
                            <td>
                                <span class="status-badge">
                                    <span class="status-dot"></span> Pending
                                </span>
                            </td>
                            <td style="text-align: right; color: var(--text-muted);">
                                <i class="fa-solid fa-chevron-right" style="font-size: 11px;"></i>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <div class="property-cell">
                                    <img src="https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=120&q=80" alt="Horizon Pool Villa" class="property-thumb">
                                    <div class="property-info">
                                        <div class="prop-name">Horizon Pool Villa</div>
                                        <div class="prop-addr">PR-2047 · 12 Ocean Ridge, Malibu, CA</div>
                                    </div>
                                </div>
                            </td>
                            <td>Daniel Carter</td>
                            <td>
                                <div class="price-main">$2,450,000</div>
                                <div class="price-sub">Min $2,200,000</div>
                            </td>
                            <td style="color: var(--text-muted);">May 23, 2025</td>
                            <td>
                                <span class="status-badge">
                                    <span class="status-dot"></span> Admin Review
                                </span>
                            </td>
                            <td style="text-align: right; color: var(--text-muted);">
                                <i class="fa-solid fa-chevron-right" style="font-size: 11px;"></i>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <div class="property-cell">
                                    <img src="https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=120&q=80" alt="The Brighton House" class="property-thumb">
                                    <div class="property-info">
                                        <div class="prop-name">The Brighton House</div>
                                        <div class="prop-addr">PR-2044 · 19 Brighton Ave, Scottsdale, AZ</div>
                                    </div>
                                </div>
                            </td>
                            <td>Sophia Morgan</td>
                            <td>
                                <div class="price-main">$890,000</div>
                                <div class="price-sub">Min $810,000</div>
                            </td>
                            <td style="color: var(--text-muted);">May 22, 2025</td>
                            <td>
                                <span class="status-badge">
                                    <span class="status-dot"></span> Pending
                                </span>
                            </td>
                            <td style="text-align: right; color: var(--text-muted);">
                                <i class="fa-solid fa-chevron-right" style="font-size: 11px;"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Bottom Grid: Live Bidding & Today's Schedule -->
            <div class="dashboard-grid-2-1">
                
                <!-- Live Bidding Card -->
                <div class="card-container">
                    <div class="card-header-flex">
                        <div>
                            <div class="card-title">Live bidding</div>
                            <div class="card-subtitle">Sessions ending soon</div>
                        </div>
                        <a href="#" class="view-all-link">View all <i class="fa-solid fa-arrow-right"></i></a>
                    </div>

                    <div class="live-banner">
                        <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=900&q=80" alt="Silver Oak Estate">
                        <div class="live-banner-overlay">
                            <span class="live-pill">
                                <span class="status-dot" style="background: var(--status-active-text);"></span> LIVE
                            </span>
                            <div>
                                <div style="color: #fbbf24; font-size: 11px; font-weight: 600;">Ends in 02:18:42</div>
                                <div style="color: white; font-size: 18px; font-weight: 700; margin-top: 2px;">Silver Oak Estate</div>
                                <div style="color: #cbd5e1; font-size: 12px; margin-top: 2px;">
                                    <i class="fa-solid fa-location-dot"></i> Beverly Hills, CA
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="live-metrics-row">
                        <div>
                            <div class="price-sub">Current bid</div>
                            <div class="price-main" style="font-size: 15px; margin-top: 2px;">$3,240,000</div>
                        </div>
                        <div>
                            <div class="price-sub">Total bids</div>
                            <div class="price-main" style="font-size: 15px; margin-top: 2px;">46</div>
                        </div>
                        <div>
                            <div class="price-sub">Highest bidder</div>
                            <div class="price-main" style="font-size: 15px; margin-top: 2px;">A. Richards</div>
                        </div>
                    </div>
                </div>

                <!-- Today's Schedule -->
                <div class="card-container">
                    <div class="card-header-flex" style="margin-bottom: 24px;">
                        <div>
                            <div class="card-title">Today's schedule</div>
                            <div class="card-subtitle">Reviews and bidding events</div>
                        </div>
                        <button class="action-dots"><i class="fa-regular fa-calendar"></i></button>
                    </div>

                    <div class="timeline-list">
                        <div class="timeline-item">
                            <div class="timeline-time">10:30</div>
                            <div class="timeline-dot" style="background: var(--accent-orange);"></div>
                            <div>
                                <div class="prop-name" style="font-size: 12px; font-weight: 600;">Legal document review</div>
                                <div class="prop-addr" style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Cedar Grove Residence</div>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-time">13:00</div>
                            <div class="timeline-dot" style="background: var(--status-active-text);"></div>
                            <div>
                                <div class="prop-name" style="font-size: 12px; font-weight: 600;">Admin approval meeting</div>
                                <div class="prop-addr" style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">4 properties · Boardroom</div>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-time">16:45</div>
                            <div class="timeline-dot" style="background: var(--sidebar-bg);"></div>
                            <div>
                                <div class="prop-name" style="font-size: 12px; font-weight: 600;">Bidding session closes</div>
                                <div class="prop-addr" style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Silver Oak Estate</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

</body>
</html>