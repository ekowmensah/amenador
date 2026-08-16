<section class="admin-intelligence" id="admin_intelligence" aria-labelledby="admin_intelligence_title">
    <div class="admin-intelligence__heading">
        <div>
            <span class="dashboard-eyebrow">Admin intelligence</span>
            <h2 id="admin_intelligence_title">Executive command centre</h2>
            <p>Performance, financial health, inventory exposure and operational risk in one view.</p>
        </div>
        <div class="admin-intelligence__status"><span></span><b>Live business view</b><small id="admin_analytics_updated">Loading…</small></div>
    </div>

    <div class="admin-kpi-grid" id="admin_kpi_grid">
        @for ($i = 0; $i < 6; $i++)
            <article class="admin-kpi-card is-loading"><div class="admin-skeleton"></div></article>
        @endfor
    </div>

    <div class="admin-analytics-grid">
        <article class="admin-insight-card admin-insight-card--wide">
            <header><div><span>Performance</span><h3>Revenue and expense movement</h3></div><i class="fas fa-chart-line"></i></header>
            <div class="admin-chart" id="admin_trend_chart" aria-label="Revenue and expense trend"></div>
        </article>
        <article class="admin-insight-card">
            <header><div><span>Collections</span><h3>Payment mix</h3></div><i class="fas fa-wallet"></i></header>
            <div class="admin-chart admin-chart--compact" id="admin_payment_chart"></div>
        </article>
        <article class="admin-insight-card">
            <header><div><span>Credit control</span><h3>Receivable ageing</h3></div><i class="fas fa-hourglass-half"></i></header>
            <div class="admin-chart admin-chart--compact" id="admin_ageing_chart"></div>
        </article>
        <article class="admin-insight-card admin-insight-card--wide">
            <header><div><span>Branches</span><h3>Location performance</h3></div><i class="fas fa-store-alt"></i></header>
            <div class="admin-table-wrap"><table class="admin-data-table"><thead><tr><th>Location</th><th>Sales</th><th>Expenses</th><th>Transactions</th><th>Average sale</th></tr></thead><tbody id="admin_locations_body"></tbody></table></div>
        </article>
        <article class="admin-insight-card">
            <header><div><span>Inventory</span><h3>Stock health</h3></div><i class="fas fa-boxes"></i></header>
            <div id="admin_inventory" class="admin-stat-stack"></div>
        </article>
        <article class="admin-insight-card">
            <header><div><span>Attention</span><h3>Risk and exceptions</h3></div><i class="fas fa-shield-alt"></i></header>
            <div id="admin_risk_feed" class="admin-risk-feed"></div>
        </article>
        <article class="admin-insight-card">
            <header><div><span>Team</span><h3>Sales by cashier</h3></div><i class="fas fa-user-tie"></i></header>
            <div id="admin_staff" class="admin-ranking"></div>
        </article>
        <article class="admin-insight-card">
            <header><div><span>Products</span><h3>Top products</h3></div><i class="fas fa-trophy"></i></header>
            <div id="admin_products" class="admin-ranking"></div>
        </article>
    </div>
    <div class="admin-analytics-error" id="admin_analytics_error" hidden><i class="fas fa-exclamation-circle"></i><span>Executive analytics could not be loaded.</span><button type="button" id="retry_admin_analytics">Retry</button></div>
</section>
