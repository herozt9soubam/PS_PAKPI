package com.pspakpi.offline;

import android.annotation.SuppressLint;
import android.os.Bundle;
import android.view.View;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.LinearLayout;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;

public class MainActivity extends AppCompatActivity {

    private static final String BASE_URL = "http://127.0.0.1:8080/ps-pakpi/";
    private static final String CLIENT_URL = BASE_URL + "index.php";
    private static final String QUICK_QUOTE_URL = BASE_URL + "contact.php";
    private static final String ADMIN_URL = BASE_URL + "admin/login.php";
    private static final String LOGOUT_URL = BASE_URL + "admin/logout.php";
    private static final String SETUP_HTML = "<html><body style='font-family:sans-serif;background:#f6f8fc;margin:0;padding:24px;'>"
        + "<div style='background:#fff;border:1px solid #e4e9f3;border-radius:12px;padding:22px;'>"
        + "<h2 style='color:#0e1b3d;margin:0 0 10px;'>P.S Pakpi setup needed</h2>"
        + "<p style='color:#516078;'>Local server is not reachable at <b>127.0.0.1:8080</b>.</p>"
        + "<p style='color:#516078;'>Start KSWEB/XAMPP and keep the project in <b>/storage/emulated/0/htdocs/ps-pakpi</b>.</p>"
        + "</div></body></html>";

    private LinearLayout homeView;
    private LinearLayout webContainer;
    private WebView webView;
    private TextView webTitle;

    @SuppressLint("SetJavaScriptEnabled")
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        homeView = findViewById(R.id.homeView);
        webContainer = findViewById(R.id.webContainer);
        webView = findViewById(R.id.webView);
        webTitle = findViewById(R.id.webTitle);

        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setAllowFileAccess(true);
        settings.setAllowContentAccess(true);
        settings.setCacheMode(WebSettings.LOAD_NO_CACHE);
        settings.setLoadWithOverviewMode(true);
        settings.setUseWideViewPort(true);

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public void onReceivedError(WebView view, int errorCode, String description, String failingUrl) {
                view.loadDataWithBaseURL(null, SETUP_HTML, "text/html", "UTF-8", null);
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                super.onPageFinished(view, url);
                updateWebTitle(url);
            }
        });

        bindNavigation();
    }

    private void bindNavigation() {
        findViewById(R.id.btnExploreServices).setOnClickListener(v -> openWeb(CLIENT_URL, "Services"));
        findViewById(R.id.btnQuickQuote).setOnClickListener(v -> openWeb(QUICK_QUOTE_URL, "Quick quote"));
        findViewById(R.id.btnOpenAdmin).setOnClickListener(v -> openWeb(ADMIN_URL, "Admin"));
        findViewById(R.id.navHome).setOnClickListener(v -> showHome());
        findViewById(R.id.navServices).setOnClickListener(v -> openWeb(CLIENT_URL, "Services"));
        findViewById(R.id.navAdmin).setOnClickListener(v -> openWeb(ADMIN_URL, "Admin"));
        findViewById(R.id.btnBackHome).setOnClickListener(v -> showHome());
        findViewById(R.id.btnClient).setOnClickListener(v -> openWeb(CLIENT_URL, "Client portal"));
        findViewById(R.id.btnAdmin).setOnClickListener(v -> openWeb(ADMIN_URL, "Admin"));
        findViewById(R.id.btnLogout).setOnClickListener(v -> {
            webView.clearCache(true);
            webView.loadUrl(LOGOUT_URL);
            webView.postDelayed(() -> openWeb(CLIENT_URL, "Client portal"), 600);
        });

        findViewById(R.id.cardBakery).setOnClickListener(v -> openService("Bakery", "Bakery"));
        findViewById(R.id.cardConstruction).setOnClickListener(v -> openService("Construction Works", "Construction Works"));
        findViewById(R.id.cardConsultancy).setOnClickListener(v -> openService("Consultancy", "Consultancy"));
        findViewById(R.id.cardSupply).setOnClickListener(v -> openService("Supply Works", "Supply Works"));
        findViewById(R.id.cardManyMore).setOnClickListener(v -> openService("Many More", "Many More"));
    }

    private void openService(int serviceId, String title) {
        openWeb(BASE_URL + "service_detail.php?id=" + serviceId, title);
    }

    private void openService(String serviceName, String title) {
        openWeb(BASE_URL + "service_detail.php?service=" + serviceName.replace(" ", "%20"), title);
    }

    private void openWeb(String url, String title) {
        webTitle.setText(title);
        homeView.setVisibility(View.GONE);
        webContainer.setVisibility(View.VISIBLE);
        webView.clearHistory();
        webView.clearCache(true);
        webView.loadUrl(url);
    }

    private void showHome() {
        webView.stopLoading();
        webContainer.setVisibility(View.GONE);
        homeView.setVisibility(View.VISIBLE);
    }

    private void updateWebTitle(String url) {
        if (url == null) {
            return;
        }
        if (url.contains("/admin/")) {
            webTitle.setText("Admin");
        } else if (url.contains("service_detail.php")) {
            if (webTitle.getText() == null || webTitle.getText().length() == 0) {
                webTitle.setText("Service detail");
            }
        } else {
            webTitle.setText("Client portal");
        }
    }

    @Override
    protected void onResume() {
        super.onResume();
        webView.clearCache(true);
    }

    @Override
    public void onBackPressed() {
        if (webContainer.getVisibility() == View.VISIBLE && webView.canGoBack()) {
            webView.goBack();
        } else if (webContainer.getVisibility() == View.VISIBLE) {
            showHome();
        } else {
            super.onBackPressed();
        }
    }
}
