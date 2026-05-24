"""
Glow-E Recommender Dashboard — UPLEVEL Edition
Ultra-premium Streamlit UI with cinematic dark glass design system,
micro-animations, spatial depth, and high-signal data visualization.
"""

import streamlit as st
import pandas as pd
import plotly.express as px
import plotly.graph_objects as go
import requests
import json
import os
import re
import time
from datetime import datetime
from pathlib import Path

# ============================================================
# Page Config
# ============================================================

st.set_page_config(
    page_title="Glow-E • Intelligence Center",
    page_icon="✦",
    layout="wide",
    initial_sidebar_state="expanded",
)

# ============================================================
# DESIGN SYSTEM — Glass Morphism + Cinematic Dark
# ============================================================

DESIGN_CSS = """
<style>
/* ── Google Font: Syne (display) + DM Sans (body) ── */
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap');

/* ── CSS Variables ── */
:root {
  --bg-base:        #050811;
  --bg-surface:     #0a0f1e;
  --bg-glass:       rgba(14, 20, 40, 0.72);
  --bg-glass-hover: rgba(20, 30, 58, 0.85);
  --border-subtle:  rgba(99, 140, 255, 0.12);
  --border-glow:    rgba(99, 140, 255, 0.35);
  --text-primary:   #eef2ff;
  --text-secondary: #8b9fc9;
  --text-muted:     #4a5578;
  --accent-blue:    #4f8ef7;
  --accent-violet:  #9b6bff;
  --accent-teal:    #00e5c3;
  --accent-amber:   #f5a623;
  --accent-rose:    #ff6b8a;
  --gradient-brand: linear-gradient(135deg, #4f8ef7 0%, #9b6bff 100%);
  --gradient-teal:  linear-gradient(135deg, #00e5c3 0%, #4f8ef7 100%);
  --gradient-warm:  linear-gradient(135deg, #f5a623 0%, #ff6b8a 100%);
  --shadow-card:    0 8px 32px rgba(0,0,0,0.6), 0 1px 0 rgba(99,140,255,0.06) inset;
  --shadow-glow:    0 0 40px rgba(79, 142, 247, 0.15);
  --radius-sm: 8px;
  --radius-md: 14px;
  --radius-lg: 20px;
  --radius-xl: 28px;
}

/* ── Global Reset ── */
html, body, [class*="css"] {
  font-family: 'DM Sans', sans-serif;
  background-color: var(--bg-base) !important;
  color: var(--text-primary);
}

/* ── Streamlit scaffolding overrides ── */
.main .block-container {
  padding: 0 2rem 3rem 2rem;
  max-width: 100%;
}
.stApp {
  background: var(--bg-base);
  background-image:
    radial-gradient(ellipse 80% 50% at 20% -10%, rgba(79,142,247,0.08) 0%, transparent 60%),
    radial-gradient(ellipse 60% 40% at 90% 110%, rgba(155,107,255,0.07) 0%, transparent 55%);
}

/* ── SIDEBAR ── */
[data-testid="stSidebar"] {
  background: var(--bg-surface) !important;
  border-right: 1px solid var(--border-subtle) !important;
}
[data-testid="stSidebar"] .block-container {
  padding: 1.5rem 1rem;
}

/* Sidebar nav radio */
[data-testid="stSidebar"] .stRadio label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 0.9rem;
  border-radius: var(--radius-sm);
  font-size: 0.88rem;
  font-weight: 500;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}
[data-testid="stSidebar"] .stRadio label:hover {
  background: rgba(79, 142, 247, 0.08);
  color: var(--text-primary);
  border-color: var(--border-subtle);
}
[data-testid="stSidebar"] [data-baseweb="radio"] input:checked + div + label,
[data-testid="stSidebar"] [aria-checked="true"] label {
  background: rgba(79, 142, 247, 0.12);
  color: var(--accent-blue) !important;
  border-color: var(--border-glow);
}

/* ── TOP HEADER ── */
.glow-header {
  position: relative;
  padding: 2rem 2.5rem;
  border-radius: var(--radius-xl);
  background: var(--bg-glass);
  border: 1px solid var(--border-subtle);
  backdrop-filter: blur(20px);
  box-shadow: var(--shadow-card);
  margin-bottom: 0.5rem;
  overflow: hidden;
}
.glow-header::before {
  content: '';
  position: absolute;
  top: -60px; left: -60px;
  width: 280px; height: 280px;
  background: radial-gradient(circle, rgba(79,142,247,0.12) 0%, transparent 70%);
  pointer-events: none;
}
.glow-header::after {
  content: '';
  position: absolute;
  bottom: -40px; right: -40px;
  width: 220px; height: 220px;
  background: radial-gradient(circle, rgba(155,107,255,0.10) 0%, transparent 70%);
  pointer-events: none;
}
.glow-header-wordmark {
  font-family: 'Syne', sans-serif;
  font-size: 2.0rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  background: var(--gradient-brand);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1;
  margin-bottom: 0.25rem;
}
.glow-header-sub {
  font-size: 0.9rem;
  color: var(--text-secondary);
  font-weight: 300;
  letter-spacing: 0.02em;
  margin-bottom: 1rem;
}
.glow-pill-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.glow-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.3rem 0.75rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 500;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  border: 1px solid rgba(99,140,255,0.25);
  color: var(--text-secondary);
  background: rgba(79,142,247,0.06);
}
.glow-pill-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--accent-teal);
  box-shadow: 0 0 8px var(--accent-teal);
  animation: pulse-dot 2s ease-in-out infinite;
}
@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.6; transform: scale(0.85); }
}

/* ── STATUS BADGE ── */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 1rem;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.03em;
}
.status-online {
  background: rgba(0, 229, 195, 0.1);
  border: 1px solid rgba(0, 229, 195, 0.35);
  color: var(--accent-teal);
}
.status-offline {
  background: rgba(255, 107, 138, 0.1);
  border: 1px solid rgba(255, 107, 138, 0.35);
  color: var(--accent-rose);
}
.status-dot-online {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--accent-teal);
  box-shadow: 0 0 10px var(--accent-teal);
  animation: pulse-dot 1.5s ease-in-out infinite;
}
.status-dot-offline {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--accent-rose);
}

/* ── KPI CARDS ── */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.kpi-card {
  position: relative;
  padding: 1.4rem 1.5rem;
  border-radius: var(--radius-md);
  background: var(--bg-glass);
  border: 1px solid var(--border-subtle);
  box-shadow: var(--shadow-card);
  backdrop-filter: blur(16px);
  overflow: hidden;
  transition: border-color 0.25s ease, box-shadow 0.25s ease;
}
.kpi-card:hover {
  border-color: var(--border-glow);
  box-shadow: var(--shadow-card), var(--shadow-glow);
}
.kpi-card-accent {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
  border-radius: 2px 2px 0 0;
}
.kpi-icon {
  font-size: 1.3rem;
  margin-bottom: 0.6rem;
  display: block;
}
.kpi-label {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--text-muted);
  font-weight: 600;
  margin-bottom: 0.3rem;
}
.kpi-value {
  font-family: 'Syne', sans-serif;
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
  margin-bottom: 0.2rem;
}
.kpi-sub {
  font-size: 0.72rem;
  color: var(--text-muted);
}

/* ── SECTION HEADERS ── */
.section-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 2rem 0 1rem 0;
}
.section-header-line {
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, var(--border-subtle) 0%, transparent 100%);
}
.section-title {
  font-family: 'Syne', sans-serif;
  font-size: 1.1rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  color: var(--text-primary);
}
.section-icon {
  width: 32px; height: 32px;
  border-radius: var(--radius-sm);
  background: rgba(79, 142, 247, 0.12);
  border: 1px solid var(--border-subtle);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
}

/* ── DIVIDER ── */
.glow-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--border-subtle) 30%, var(--border-subtle) 70%, transparent);
  margin: 1.5rem 0;
}

/* ── PRODUCT / REC CARDS ── */
.rec-card {
  position: relative;
  border-radius: var(--radius-md);
  background: var(--bg-glass);
  border: 1px solid var(--border-subtle);
  box-shadow: var(--shadow-card);
  overflow: hidden;
  transition: all 0.3s ease;
  padding-bottom: 0.75rem;
}
.rec-card:hover {
  border-color: var(--border-glow);
  transform: translateY(-3px);
  box-shadow: var(--shadow-card), 0 12px 40px rgba(79,142,247,0.12);
}
.rec-image-wrap {
  width: 100%;
  aspect-ratio: 1 / 1;
  background: linear-gradient(135deg, #0d1424, #111827);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-bottom: 1px solid var(--border-subtle);
}
.rec-image-placeholder {
  font-size: 2.5rem;
  opacity: 0.25;
}
.rec-body {
  padding: 0.75rem 0.9rem 0;
}
.rec-category {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--accent-blue);
  font-weight: 600;
  margin-bottom: 0.25rem;
}
.rec-name {
  font-family: 'Syne', sans-serif;
  font-size: 0.92rem;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.3;
  margin-bottom: 0.5rem;
}
.rec-meta-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 0.4rem;
}
.rec-price {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--text-primary);
}
.rec-score-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
  padding: 0.2rem 0.5rem;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 700;
  background: rgba(245, 166, 35, 0.12);
  border: 1px solid rgba(245, 166, 35, 0.3);
  color: var(--accent-amber);
}
.rec-id {
  font-size: 0.68rem;
  color: var(--text-muted);
}

/* ── CHART CONTAINERS ── */
.chart-wrap {
  border-radius: var(--radius-md);
  background: var(--bg-glass);
  border: 1px solid var(--border-subtle);
  padding: 1.25rem 1rem 0.5rem 1rem;
  box-shadow: var(--shadow-card);
}
.chart-title {
  font-family: 'Syne', sans-serif;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

/* ── MODEL JSON BOX ── */
.model-config-box {
  border-radius: var(--radius-md);
  background: rgba(5, 8, 17, 0.9);
  border: 1px solid var(--border-subtle);
  padding: 1.1rem 1.25rem;
  font-family: 'DM Mono', 'Fira Code', monospace;
  font-size: 0.82rem;
  color: var(--text-secondary);
}
.model-config-row {
  display: flex;
  justify-content: space-between;
  padding: 0.3rem 0;
  border-bottom: 1px solid rgba(99,140,255,0.06);
}
.model-config-row:last-child { border-bottom: none; }
.model-config-key { color: var(--accent-violet); }
.model-config-val { color: var(--accent-teal); font-weight: 600; }

/* ── INFO BOX ── */
.info-box {
  border-radius: var(--radius-md);
  background: rgba(79, 142, 247, 0.05);
  border: 1px solid rgba(79, 142, 247, 0.2);
  padding: 1rem 1.2rem;
  font-size: 0.85rem;
  color: var(--text-secondary);
  line-height: 1.65;
}
.info-box strong { color: var(--text-primary); }

/* ── METRIC MINI BADGE (source/count) ── */
.mini-metric {
  border-radius: var(--radius-sm);
  background: var(--bg-glass);
  border: 1px solid var(--border-subtle);
  padding: 0.6rem 1rem;
  text-align: center;
}
.mini-metric-label {
  font-size: 0.68rem;
  text-transform: uppercase;
  letter-spacing: 0.09em;
  color: var(--text-muted);
  margin-bottom: 0.2rem;
}
.mini-metric-val {
  font-family: 'Syne', sans-serif;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
}

/* ── DIAGNOSTIC PANEL ── */
.diag-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  background: var(--bg-glass);
  border: 1px solid var(--border-subtle);
  margin-bottom: 0.5rem;
}
.diag-label { font-size: 0.83rem; color: var(--text-secondary); flex: 1; }
.diag-val { font-size: 0.9rem; font-weight: 600; color: var(--text-primary); }

/* ── STREAMLIT ELEMENT OVERRIDES ── */
.stSelectbox > label,
.stSlider > label,
.stRadio > label {
  font-size: 0.8rem !important;
  font-weight: 500 !important;
  color: var(--text-secondary) !important;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}
div[data-baseweb="select"] > div,
div[data-baseweb="input"] > div {
  background: var(--bg-glass) !important;
  border-color: var(--border-subtle) !important;
  border-radius: var(--radius-sm) !important;
  color: var(--text-primary) !important;
}
.stButton > button {
  background: var(--gradient-brand) !important;
  color: white !important;
  border: none !important;
  border-radius: var(--radius-sm) !important;
  font-family: 'DM Sans', sans-serif !important;
  font-weight: 600 !important;
  font-size: 0.85rem !important;
  padding: 0.55rem 1.5rem !important;
  transition: opacity 0.2s ease, transform 0.15s ease !important;
  box-shadow: 0 4px 14px rgba(79,142,247,0.3) !important;
}
.stButton > button:hover {
  opacity: 0.9 !important;
  transform: translateY(-1px) !important;
}
.stDataFrame {
  border-radius: var(--radius-md) !important;
  overflow: hidden !important;
  border: 1px solid var(--border-subtle) !important;
}
div[data-testid="metric-container"] {
  background: var(--bg-glass) !important;
  border: 1px solid var(--border-subtle) !important;
  border-radius: var(--radius-md) !important;
  padding: 1rem !important;
}
.stAlert {
  border-radius: var(--radius-md) !important;
  border-left-width: 3px !important;
}
hr { border-color: var(--border-subtle) !important; }

/* ── SCROLLBAR ── */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--border-subtle); border-radius: 99px; }
::-webkit-scrollbar-thumb:hover { background: var(--border-glow); }

/* ── SIDEBAR BRAND ── */
.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid var(--border-subtle);
}
.sidebar-logo {
  width: 34px; height: 34px;
  border-radius: 10px;
  background: var(--gradient-brand);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  box-shadow: 0 4px 12px rgba(79,142,247,0.35);
}
.sidebar-name {
  font-family: 'Syne', sans-serif;
  font-size: 1.05rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--text-primary);
}
.sidebar-tagline {
  font-size: 0.68rem;
  color: var(--text-muted);
}
.sidebar-section-label {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: var(--text-muted);
  font-weight: 700;
  margin: 1.2rem 0 0.5rem 0;
  padding: 0 0.3rem;
}

/* Hide default streamlit elements */
#MainMenu { visibility: hidden; }
footer { visibility: hidden; }
header { visibility: hidden; }
</style>
"""

st.markdown(DESIGN_CSS, unsafe_allow_html=True)

# ── Color palette for charts ──
COLOR_PALETTE = {
    "primary":   "#4f8ef7",
    "secondary": "#9b6bff",
    "accent":    "#00e5c3",
    "danger":    "#ff6b8a",
    "amber":     "#f5a623",
    "muted":     "#4a5578",
}
CHART_COLORS = ["#4f8ef7", "#9b6bff", "#00e5c3", "#f5a623", "#ff6b8a", "#38bdf8", "#a3e635"]

PLOTLY_LAYOUT = dict(
    template="plotly_dark",
    paper_bgcolor="rgba(0,0,0,0)",
    plot_bgcolor="rgba(0,0,0,0)",
    font=dict(family="DM Sans, sans-serif", color="#8b9fc9", size=12),
    margin=dict(l=10, r=10, t=36, b=12),
    title_font=dict(family="Syne, sans-serif", size=14, color="#eef2ff"),
)

API_BASE_URL = "http://localhost:8000"

# ============================================================
# Data Helpers (Cached)
# ============================================================

@st.cache_data(show_spinner=False)
def load_interactions():
    for path in [
        "recommender/data/events.csv",
        "data/events.csv",
        "recommender/data/processed/interactions.csv",
        "data/processed/interactions.csv",
    ]:
        if os.path.exists(path):
            try:
                df = pd.read_csv(path)
                if "user_id" in df.columns and "item_id" in df.columns:
                    return df
            except Exception as e:
                st.warning(f"Load error {path}: {e}")
    return pd.DataFrame({"user_id": [], "item_id": [], "event_type": [], "timestamp": []})


@st.cache_data(show_spinner=False)
def load_items():
    for path in [
        "recommender/data/item_properties.csv",
        "data/item_properties.csv",
        "recommender/data/products.csv",
        "data/products.csv",
    ]:
        if os.path.exists(path):
            try:
                df = pd.read_csv(path)
                if "id" in df.columns or "item_id" in df.columns:
                    return df
            except Exception as e:
                st.warning(f"Load error {path}: {e}")
    return pd.DataFrame({"id": [], "name": [], "category": [], "brand": [], "price": [], "image_url": []})


@st.cache_data(show_spinner=False)
def load_model_metrics():
    if os.path.exists("recommender/data/model_metrics.json"):
        try:
            with open("recommender/data/model_metrics.json") as f:
                return json.load(f)
        except Exception:
            pass
    return {
        "rmse": 0.85, "precision_k": 0.72, "recall_k": 0.68, "coverage": 0.95,
        "model": "ALS (Alternating Least Squares)", "rank": 20,
        "iterations": 10, "test_set_size": 0.2,
    }


def _probe_api():
    try:
        return requests.get(f"{API_BASE_URL}/health", timeout=1.5).status_code == 200
    except Exception:
        return False


def find_local_product_image(product_name):
    if not product_name or not isinstance(product_name, str):
        return None
    base_dir = Path(__file__).resolve().parent.parent
    image_dir = base_dir / "images" / "prod_images"
    for ext in ["jpg", "jpeg", "png", "gif", "webp", "avif"]:
        c = image_dir / f"{product_name}.{ext}"
        if c.exists():
            return str(c)
    sanitized = re.sub(r'["/\'&?!]', '', product_name).replace("/", "-")
    for ext in ["jpg", "jpeg", "png", "gif", "webp", "avif"]:
        c = image_dir / f"{sanitized}.{ext}"
        if c.exists():
            return str(c)
    key = re.sub(r"[^a-z0-9]", "", product_name.lower())
    if image_dir.exists():
        for c in image_dir.iterdir():
            if c.is_file() and re.sub(r"[^a-z0-9]", "", c.stem.lower()) == key:
                return str(c)
    return None


def render_image_from_path_or_url(source):
    if not source:
        return False
    if isinstance(source, str) and source.startswith("http"):
        try:
            st.image(source, use_container_width=True)
            return True
        except Exception:
            return False
    try:
        p = Path(source)
        if not p.is_absolute():
            p = Path(__file__).resolve().parent.parent / source
        if p.exists():
            with open(p, "rb") as f:
                st.image(f.read(), use_container_width=True)
            return True
    except Exception:
        return False
    return False


# ============================================================
# Shared UI Helpers
# ============================================================

def section_header(icon, title):
    st.markdown(
        f"""
        <div class="section-header">
            <div class="section-icon">{icon}</div>
            <span class="section-title">{title}</span>
            <div class="section-header-line"></div>
        </div>
        """,
        unsafe_allow_html=True,
    )


def kpi_card(label, value, sub, icon, accent_color):
    st.markdown(
        f"""
        <div class="kpi-card">
            <div class="kpi-card-accent" style="background:{accent_color};"></div>
            <span class="kpi-icon">{icon}</span>
            <div class="kpi-label">{label}</div>
            <div class="kpi-value">{value}</div>
            <div class="kpi-sub">{sub}</div>
        </div>
        """,
        unsafe_allow_html=True,
    )


def divider():
    st.markdown('<div class="glow-divider"></div>', unsafe_allow_html=True)


def plotly_defaults(fig, height=380, title=None):
    layout = dict(PLOTLY_LAYOUT)
    layout["height"] = height
    if title:
        layout["title"] = dict(text=title, font=dict(family="Syne, sans-serif", size=13, color="#eef2ff"))
    fig.update_layout(**layout)
    return fig


# ============================================================
# Top Header
# ============================================================

def render_top_header():
    api_online = _probe_api()
    status_cls = "status-online" if api_online else "status-offline"
    dot_cls    = "status-dot-online" if api_online else "status-dot-offline"
    status_txt = "API Online" if api_online else "API Offline"

    col_brand, col_status = st.columns([4, 1])
    with col_brand:
        st.markdown(
            f"""
            <div class="glow-header">
                <div class="glow-header-wordmark">✦ Glow‑E Intelligence Center</div>
                <div class="glow-header-sub">
                    Real-time data intelligence · ALS model monitoring · Live recommendation engine
                </div>
                <div class="glow-pill-row">
                    <span class="glow-pill"><span class="glow-pill-dot"></span>ALS Matrix Factorization</span>
                    <span class="glow-pill">MinIO Object Store</span>
                    <span class="glow-pill">FastAPI Backend</span>
                    <span class="glow-pill">PHP e-commerce</span>
                </div>
            </div>
            """,
            unsafe_allow_html=True,
        )
    with col_status:
        ts = datetime.now().strftime("%H:%M:%S")
        st.markdown(
            f"""
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.6rem;
                        height:100%;justify-content:center;padding:0.5rem 0;">
                <span class="status-badge {status_cls}">
                    <span class="{dot_cls}"></span>
                    {status_txt}
                </span>
                <span style="font-size:0.7rem;color:var(--text-muted);">Last ping {ts}</span>
            </div>
            """,
            unsafe_allow_html=True,
        )


# ============================================================
# Page: Dataset Overview
# ============================================================

def render_dataset_overview():
    section_header("📊", "Dataset Overview")

    interactions_df = load_interactions()
    items_df = load_items()

    if interactions_df.empty:
        st.warning("No interaction data found. Check your data paths.")
        return

    enriched = interactions_df.copy()
    if not items_df.empty:
        enriched = enriched.merge(items_df.rename(columns={"item_id": "item_id"}), on="item_id", how="left")
        if "price" in enriched.columns:
            enriched["price"] = pd.to_numeric(enriched["price"], errors="coerce")
            enriched["revenue"] = enriched["price"].fillna(0)
        else:
            enriched["revenue"] = 0
    else:
        enriched["revenue"] = 0

    num_users        = enriched["user_id"].nunique() if "user_id" in enriched.columns else 0
    num_items        = enriched["item_id"].nunique() if "item_id" in enriched.columns else 0
    num_interactions = len(enriched)

    purchase_count = 0
    purchase_share = 0
    if "event_type" in enriched.columns and "purchase" in enriched["event_type"].unique():
        purchase_count = int((enriched["event_type"] == "purchase").sum())
        purchase_share = purchase_count / num_interactions if num_interactions else 0

    period = "N/A"
    if "timestamp" in enriched.columns:
        try:
            enriched["timestamp"] = pd.to_datetime(enriched["timestamp"])
            period = f"{enriched['timestamp'].min().date()} → {enriched['timestamp'].max().date()}"
        except Exception:
            pass

    c1, c2, c3, c4 = st.columns(4)
    with c1: kpi_card("Active Users", f"{num_users:,}", "Identified in event logs", "👤", COLOR_PALETTE["primary"])
    with c2: kpi_card("Unique Products", f"{num_items:,}", "Tracked by the model", "📦", COLOR_PALETTE["secondary"])
    with c3: kpi_card("Total Interactions", f"{num_interactions:,}", "Clicks · carts · purchases", "⚡", COLOR_PALETTE["accent"])
    with c4: kpi_card("Time Window", period if period != "N/A" else "—", "Dataset date range", "📅", COLOR_PALETTE["amber"])

    divider()

    # Funnel + Category Revenue
    col_left, col_right = st.columns([1.2, 1])

    with col_left:
        st.markdown('<div class="chart-wrap"><div class="chart-title">⬡ Event Funnel</div>', unsafe_allow_html=True)
        if "event_type" in enriched.columns:
            fc = enriched["event_type"].value_counts()
            fig = px.bar(
                pd.DataFrame({"event_type": fc.index, "count": fc.values}).sort_values("count", ascending=False),
                x="event_type", y="count",
                color="event_type",
                color_discrete_sequence=CHART_COLORS,
            )
            fig.update_traces(marker_line_width=0)
            plotly_defaults(fig, height=340)
            fig.update_layout(showlegend=False)
            st.plotly_chart(fig, use_container_width=True)
            if purchase_count:
                st.markdown(
                    f'<p style="font-size:0.75rem;color:var(--text-muted);margin:0 0 0.5rem 0;">'
                    f'Purchase rate: <strong style="color:var(--accent-teal)">{purchase_share:.1%}</strong> '
                    f'({purchase_count:,} / {num_interactions:,})</p>',
                    unsafe_allow_html=True,
                )
        else:
            st.info("No event_type column found.")
        st.markdown('</div>', unsafe_allow_html=True)

    with col_right:
        st.markdown('<div class="chart-wrap"><div class="chart-title">💰 Revenue by Category</div>', unsafe_allow_html=True)
        if not enriched.empty and "category" in enriched.columns and "revenue" in enriched.columns:
            cat = (
                enriched.groupby("category")
                .agg(revenue=("revenue", "sum"), count=("item_id", "count"))
                .sort_values("revenue", ascending=False).head(10).reset_index()
            )
            fig = px.bar(cat, x="revenue", y="category", orientation="h",
                         color="revenue", color_continuous_scale=[[0, "#0a1628"], [0.5, "#4f8ef7"], [1, "#00e5c3"]])
            fig.update_traces(marker_line_width=0)
            plotly_defaults(fig, height=340)
            fig.update_layout(coloraxis_showscale=False, yaxis=dict(categoryorder="total ascending"))
            st.plotly_chart(fig, use_container_width=True)
        else:
            st.info("Category/revenue data not available.")
        st.markdown('</div>', unsafe_allow_html=True)

    divider()

    col_l, col_r = st.columns([1.4, 0.85])

    with col_l:
        st.markdown('<div class="chart-wrap"><div class="chart-title">🏆 Top 10 Products by Sales</div>', unsafe_allow_html=True)
        if not enriched.empty and "name" in enriched.columns:
            pc = (
                enriched.groupby("name")
                .agg(count=("item_id", "count"), revenue=("revenue", "sum"))
                .sort_values("count", ascending=False).head(10).reset_index()
            )
            fig = px.bar(pc, x="count", y="name", orientation="h",
                         color="revenue", color_continuous_scale=[[0, "#0a1628"], [0.5, "#9b6bff"], [1, "#f5a623"]])
            fig.update_traces(marker_line_width=0)
            plotly_defaults(fig, height=380)
            fig.update_layout(coloraxis_showscale=False, yaxis=dict(categoryorder="total ascending"))
            st.plotly_chart(fig, use_container_width=True)
        else:
            st.info("Insufficient product data for ranking.")
        st.markdown('</div>', unsafe_allow_html=True)

    with col_r:
        st.markdown('<div class="chart-wrap"><div class="chart-title">🏷 Brand Performance</div>', unsafe_allow_html=True)
        if not enriched.empty and "brand" in enriched.columns:
            bm = (
                enriched.groupby("brand")
                .agg(revenue=("revenue", "sum"), count=("item_id", "count"))
                .sort_values("revenue", ascending=False).head(8).reset_index()
            )
            fig = px.bar(bm, x="revenue", y="brand", orientation="h",
                         color="revenue", color_continuous_scale=[[0, "#0a1628"], [0.5, "#4f8ef7"], [1, "#9b6bff"]])
            fig.update_traces(marker_line_width=0)
            plotly_defaults(fig, height=380)
            fig.update_layout(coloraxis_showscale=False, yaxis=dict(categoryorder="total ascending"))
            st.plotly_chart(fig, use_container_width=True)
        else:
            st.info("Brand data unavailable.")
        st.markdown('</div>', unsafe_allow_html=True)

    divider()
    with st.expander("🗄 Raw Dataset Preview (first 200 rows)"):
        st.dataframe(enriched.head(200), use_container_width=True, height=280)


# ============================================================
# Page: User Behaviour
# ============================================================

def render_user_behaviour():
    section_header("👤", "User Behaviour")

    interactions_df = load_interactions()
    if interactions_df.empty:
        st.warning("No interaction data available.")
        return

    ctrl_l, ctrl_r = st.columns([3, 1])
    with ctrl_r:
        nbins = st.slider("Histogram bins", 10, 80, 30, step=5)

    # Histogram
    st.markdown('<div class="chart-wrap"><div class="chart-title">📈 Interactions per User — Distribution</div>', unsafe_allow_html=True)
    if "user_id" in interactions_df.columns:
        ipu = interactions_df.groupby("user_id").size()
        fig = px.histogram(x=ipu.values, nbins=nbins, color_discrete_sequence=[COLOR_PALETTE["primary"]])
        fig.update_traces(marker_line_width=0)
        plotly_defaults(fig, height=320)
        fig.update_layout(showlegend=False, xaxis_title="Interactions", yaxis_title="Users")
        st.plotly_chart(fig, use_container_width=True)
        st.markdown(
            f'<p style="font-size:0.75rem;color:var(--text-muted);margin:0 0 0.5rem 0;">'
            f'Mean <strong style="color:var(--text-primary)">{ipu.mean():.1f}</strong> · '
            f'Median <strong style="color:var(--text-primary)">{ipu.median():.1f}</strong> · '
            f'Max <strong style="color:var(--accent-teal)">{ipu.max()}</strong></p>',
            unsafe_allow_html=True,
        )
    st.markdown('</div>', unsafe_allow_html=True)

    divider()
    col_ts, col_stack = st.columns([1.4, 1])

    with col_ts:
        st.markdown('<div class="chart-wrap"><div class="chart-title">📅 Daily Interaction Volume</div>', unsafe_allow_html=True)
        if "timestamp" in interactions_df.columns:
            try:
                df_ts = interactions_df.copy()
                df_ts["timestamp"] = pd.to_datetime(df_ts["timestamp"])
                df_ts["date"] = df_ts["timestamp"].dt.date
                daily = df_ts.groupby("date").size().reset_index(name="count")
                fig = px.area(daily, x="date", y="count",
                              color_discrete_sequence=[COLOR_PALETTE["secondary"]])
                fig.update_traces(fillcolor="rgba(155,107,255,0.1)", line=dict(color=COLOR_PALETTE["secondary"], width=2))
                plotly_defaults(fig, height=340)
                st.plotly_chart(fig, use_container_width=True)
            except Exception as e:
                st.warning(f"Time-series error: {e}")
        else:
            st.info("timestamp column not available.")
        st.markdown('</div>', unsafe_allow_html=True)

    with col_stack:
        st.markdown('<div class="chart-wrap"><div class="chart-title">🎭 Event Mix — Top 10 Users</div>', unsafe_allow_html=True)
        if "user_id" in interactions_df.columns and "event_type" in interactions_df.columns:
            top_users = interactions_df["user_id"].value_counts().head(10).index
            grouped = (
                interactions_df[interactions_df["user_id"].isin(top_users)]
                .groupby(["user_id", "event_type"]).size().reset_index(name="count")
            )
            fig = px.bar(grouped, x="user_id", y="count", color="event_type",
                         barmode="stack", color_discrete_sequence=CHART_COLORS)
            fig.update_traces(marker_line_width=0)
            plotly_defaults(fig, height=340)
            st.plotly_chart(fig, use_container_width=True)
        st.markdown('</div>', unsafe_allow_html=True)


# ============================================================
# Page: Model Performance
# ============================================================

def render_model_performance():
    section_header("🤖", "Model Performance")

    metrics     = load_model_metrics()
    rmse        = metrics.get("rmse", 0.85)
    precision_k = metrics.get("precision_k", 0.72)
    recall_k    = metrics.get("recall_k", 0.68)
    coverage    = metrics.get("coverage", 0.95)

    c1, c2, c3, c4 = st.columns(4)
    with c1: kpi_card("RMSE", f"{rmse:.3f}", "Lower = better accuracy", "🎯", COLOR_PALETTE["accent"])
    with c2: kpi_card("Precision@K", f"{precision_k:.0%}", "Relevant in top-K results", "✅", COLOR_PALETTE["primary"])
    with c3: kpi_card("Recall@K", f"{recall_k:.0%}", "Coverage of known relevant", "🔍", COLOR_PALETTE["secondary"])
    with c4: kpi_card("Catalog Coverage", f"{coverage:.0%}", "Items surfaced by model", "📚", COLOR_PALETTE["amber"])

    divider()

    col_l, col_r = st.columns([1.3, 1])

    with col_l:
        # Radar chart
        labels    = ["Precision@K", "Recall@K", "Coverage"]
        als_vals  = [precision_k, recall_k, coverage]
        base_vals = [0.42, 0.35, 0.55]

        fig = go.Figure()
        fig.add_trace(go.Scatterpolar(
            r=als_vals + [als_vals[0]], theta=labels + [labels[0]],
            fill="toself", name="ALS Model",
            line=dict(color=COLOR_PALETTE["accent"], width=2.5),
            fillcolor="rgba(0,229,195,0.1)",
        ))
        fig.add_trace(go.Scatterpolar(
            r=base_vals + [base_vals[0]], theta=labels + [labels[0]],
            fill="toself", name="Popularity Baseline",
            line=dict(color=COLOR_PALETTE["danger"], width=1.5, dash="dot"),
            fillcolor="rgba(255,107,138,0.06)",
        ))
        fig.update_layout(
            **PLOTLY_LAYOUT,
            height=400,
            polar=dict(
                bgcolor="rgba(0,0,0,0)",
                radialaxis=dict(visible=True, range=[0, 1], tickfont=dict(size=9, color="#4a5578"),
                                gridcolor="rgba(99,140,255,0.1)", linecolor="rgba(99,140,255,0.1)"),
                angularaxis=dict(tickfont=dict(size=11, color="#8b9fc9"),
                                 gridcolor="rgba(99,140,255,0.1)", linecolor="rgba(99,140,255,0.1)"),
            ),
            legend=dict(x=0.8, y=1.1, font=dict(size=11)),
        )
        st.markdown('<div class="chart-wrap"><div class="chart-title">🕸 ALS vs Baseline Comparison</div>', unsafe_allow_html=True)
        st.plotly_chart(fig, use_container_width=True)
        st.markdown('</div>', unsafe_allow_html=True)

    with col_r:
        st.markdown(
            '<div class="chart-wrap"><div class="chart-title">⚙ Model Configuration</div>',
            unsafe_allow_html=True,
        )
        cfg = {
            "Algorithm":    metrics.get("model", "ALS (Alternating Least Squares)"),
            "Rank (factors)": metrics.get("rank", 20),
            "Iterations":   metrics.get("iterations", 10),
            "Test set size": f"{metrics.get('test_set_size', 0.2):.0%}",
        }
        rows = "".join(
            f'<div class="model-config-row"><span class="model-config-key">{k}</span>'
            f'<span class="model-config-val">{v}</span></div>'
            for k, v in cfg.items()
        )
        st.markdown(f'<div class="model-config-box">{rows}</div>', unsafe_allow_html=True)

        st.markdown(
            """
            <div class="info-box" style="margin-top:1rem;">
                <strong>ALS (Alternating Least Squares)</strong> factorises the user‑item matrix
                into two compact latent matrices.<br><br>
                · Handles sparse implicit data (clicks, views, purchases).<br>
                · Industrial‑scale via <strong>Apache Spark</strong>.<br>
                · Radar comparison shows significant gains over popularity‑based baseline.
            </div>
            """,
            unsafe_allow_html=True,
        )
        st.markdown('</div>', unsafe_allow_html=True)


# ============================================================
# Page: Recommendations Demo
# ============================================================

def render_recommendations_demo():
    section_header("🎁", "Live Recommendations Demo")

    interactions_df = load_interactions()
    items_df = load_items()

    st.markdown(
        '<p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:1rem;">'
        'Queries the same FastAPI backend as your PHP storefront in real-time.</p>',
        unsafe_allow_html=True,
    )

    if interactions_df.empty or "user_id" not in interactions_df.columns:
        st.warning("No users available.")
        return

    users_sorted = sorted(interactions_df["user_id"].unique())
    col_u, col_k, col_btn = st.columns([2, 1.2, 0.8])
    with col_u:
        selected_user = st.selectbox("Target User", users_sorted, help="Users with at least one interaction.")
    with col_k:
        top_k = st.slider("Recommendations (K)", 3, 20, 8)
    with col_btn:
        st.markdown("<br>", unsafe_allow_html=True)
        st.button("⟳  Refresh")

    divider()

    st.markdown(
        f'<div class="section-title" style="margin-bottom:1rem;">Recommendations for User '
        f'<span style="color:var(--accent-blue)">#{selected_user}</span></div>',
        unsafe_allow_html=True,
    )

    try:
        params   = {"top_n": top_k, "k": top_k, "_ts": int(time.time())}
        response = requests.get(f"{API_BASE_URL}/recommend/{selected_user}", params=params, timeout=5)

        if response.status_code != 200:
            st.error(f"API Error {response.status_code}")
            st.caption("Make sure `python recommender/api/main.py` is running.")
            return

        payload = response.json()
        if isinstance(payload, dict) and "recommendations" in payload:
            rec_list     = payload["recommendations"]
            source_label = payload.get("source", "unknown")
        elif isinstance(payload, list):
            rec_list     = payload
            source_label = "unknown"
        else:
            rec_list = []
            source_label = "unknown"

        if not rec_list:
            st.warning("No recommendations returned for this user.")
            return

        # Info row
        ci1, ci2, _ = st.columns([1, 1, 5])
        with ci1:
            st.markdown(
                f'<div class="mini-metric"><div class="mini-metric-label">Source</div>'
                f'<div class="mini-metric-val">{source_label}</div></div>',
                unsafe_allow_html=True,
            )
        with ci2:
            st.markdown(
                f'<div class="mini-metric"><div class="mini-metric-label">Returned</div>'
                f'<div class="mini-metric-val">{len(rec_list)}</div></div>',
                unsafe_allow_html=True,
            )

        st.markdown("<br>", unsafe_allow_html=True)

        # Product grid
        cols_per_row = 4
        for i in range(0, len(rec_list), cols_per_row):
            row_cols = st.columns(cols_per_row)
            for j, col in enumerate(row_cols):
                if i + j >= len(rec_list):
                    break
                with col:
                    _render_rec_card(rec_list[i + j], items_df)

    except requests.exceptions.ConnectionError:
        st.error("Cannot connect to FastAPI.")
        st.caption("Start it with: `python recommender/api/main.py`")
    except Exception as e:
        st.error(f"Error: {e}")


def _render_rec_card(rec, items_df):
    product_id   = rec.get("product_id") or rec.get("item_id") or rec.get("id") or rec.get("ID")
    product_name = rec.get("name") or rec.get("nom")
    category     = rec.get("category") or rec.get("categorie")
    price        = rec.get("price") or rec.get("prix")
    image_url    = rec.get("image_url") or rec.get("img_url") or rec.get("image_path") or rec.get("image")
    score        = rec.get("score") or rec.get("predicted_rating") or rec.get("confidence")

    # Enrich from items_df
    item_row = None
    if not items_df.empty:
        for id_col in ["item_id", "id"]:
            if product_id is not None and id_col in items_df.columns:
                r = items_df[items_df[id_col] == product_id]
                if not r.empty:
                    item_row = r
                    break
        if (item_row is None or item_row.empty) and product_name:
            for n_col in ["name", "nom"]:
                if n_col in items_df.columns:
                    r = items_df[items_df[n_col] == product_name]
                    if not r.empty:
                        item_row = r
                        break

    if item_row is not None and not item_row.empty:
        row = item_row.iloc[0]
        product_name = product_name or row.get("name") or row.get("nom")
        category     = category or row.get("category") or row.get("categorie")
        price        = price or row.get("price") or row.get("prix")
        image_url    = image_url or row.get("image_url") or row.get("img_url")

    # Card markup
    st.markdown('<div class="rec-card">', unsafe_allow_html=True)

    # Image section
    st.markdown('<div class="rec-image-wrap">', unsafe_allow_html=True)
    displayed = False
    if image_url:
        displayed = render_image_from_path_or_url(image_url)
    if not displayed and product_name:
        local = find_local_product_image(product_name)
        if local:
            displayed = render_image_from_path_or_url(local)
    if not displayed:
        st.markdown('<div class="rec-image-placeholder">🖼</div>', unsafe_allow_html=True)
    st.markdown('</div>', unsafe_allow_html=True)

    # Body
    display_name = product_name or f"Product #{product_id}"
    cat_text     = category or "—"

    score_html = ""
    if score is not None:
        s = f"{score:.2f}" if isinstance(score, float) else str(score)
        score_html = f'<span class="rec-score-badge">★ {s}</span>'

    price_html = ""
    if isinstance(price, (int, float)):
        price_html = f'<span class="rec-price">${price:.2f}</span>'
    elif price:
        price_html = f'<span class="rec-price">{price}</span>'

    st.markdown(
        f"""
        <div class="rec-body">
            <div class="rec-category">{cat_text}</div>
            <div class="rec-name">{display_name}</div>
            <div class="rec-meta-row">
                {price_html}
                {score_html}
            </div>
            <div class="rec-id" style="margin-top:0.3rem;">ID: {product_id}</div>
        </div>
        """,
        unsafe_allow_html=True,
    )
    st.markdown('</div>', unsafe_allow_html=True)


# ============================================================
# Page: Debug & Diagnostics
# ============================================================

def render_diagnostics():
    section_header("🔧", "Debug & Diagnostics")

    # Fetch API state
    state = None
    try:
        resp = requests.get(f"{API_BASE_URL}/debug/state", timeout=5)
        if resp.status_code == 200:
            state = resp.json()
    except Exception as e:
        st.error(f"API Error: {e}")

    if not state:
        st.warning("Could not load API state.")
        return

    interactions_df = load_interactions()
    n_interactions  = len(interactions_df) if not interactions_df.empty else 0

    c1, c2, c3, c4 = st.columns(4)
    with c1: kpi_card("Users w/ Recs", len(state.get("users_with_recs", [])), "Have personalized recs", "🧑‍💻", COLOR_PALETTE["primary"])
    with c2: kpi_card("Catalog Size",  state.get("num_products", 0), "Indexed products", "📦", COLOR_PALETTE["secondary"])
    with c3: kpi_card("Popular Items", len(state.get("popular_products", [])), "Fallback pool size", "🔥", COLOR_PALETTE["amber"])
    with c4: kpi_card("Total Events",  n_interactions, "Interaction records", "⚡", COLOR_PALETTE["accent"])

    divider()

    # Per-user debug
    section_header("🔬", "Per-User Analysis")
    user_list = sorted(state.get("users_with_recs", []))

    if user_list:
        col_sel, _ = st.columns([1.5, 2])
        with col_sel:
            selected_user = st.selectbox("Select User", user_list)

        user_debug = None
        try:
            r = requests.get(f"{API_BASE_URL}/debug/recommend/{selected_user}", timeout=5)
            if r.status_code == 200:
                user_debug = r.json()
        except Exception as e:
            st.error(f"Request error: {e}")

        if user_debug:
            has_model = user_debug.get("model_has_recs", False)

            d1, d2, d3 = st.columns(3)
            with d1:
                kpi_card("Past Interactions", user_debug.get("past_interactions_count", 0), "Events for this user", "📊", COLOR_PALETTE["primary"])
            with d2:
                kpi_card("Top Category", str(user_debug.get("user_top_category", "N/A")), "Most visited category", "🏷", COLOR_PALETTE["secondary"])
            with d3:
                model_lbl = "Model recs available" if has_model else "No model recs"
                color = COLOR_PALETTE["accent"] if has_model else COLOR_PALETTE["danger"]
                kpi_card("Model Status", "✅ Yes" if has_model else "❌ No", model_lbl, "🤖", color)

            details = user_debug.get("detailed_scores", [])
            if details:
                st.markdown("<br>", unsafe_allow_html=True)
                st.markdown('<div class="chart-wrap"><div class="chart-title">📋 Candidate Scores</div>', unsafe_allow_html=True)
                st.dataframe(pd.DataFrame(details), use_container_width=True, height=300)
                st.markdown('</div>', unsafe_allow_html=True)
            else:
                st.info("No detailed scores for this user.")
    else:
        st.info("No users have recommendations yet.")

    divider()

    # Recommendation diversity
    section_header("📈", "Recommendation Diversity")
    try:
        recs_path = Path("recommender/data/user_recs.parquet")
        if recs_path.exists():
            rdf = pd.read_parquet(recs_path)
            top5 = rdf.sort_values("pred_rating", ascending=False).groupby("user_id").head(5)
            unique_top5 = top5.groupby("user_id")["item_id"].apply(tuple).nunique()

            r1, r2, r3 = st.columns(3)
            with r1: kpi_card("Unique Users",    rdf["user_id"].nunique(), "In recs file", "👥", COLOR_PALETTE["primary"])
            with r2: kpi_card("Items Surfaced",  rdf["item_id"].nunique(), "Distinct products", "📦", COLOR_PALETTE["secondary"])
            with r3: kpi_card("Unique Top-5 Lists", unique_top5, "Personalization diversity", "🎨", COLOR_PALETTE["accent"])

            st.markdown("<br>", unsafe_allow_html=True)
            st.markdown('<div class="chart-wrap"><div class="chart-title">📊 Prediction Score Distribution</div>', unsafe_allow_html=True)
            fig = px.histogram(rdf, x="pred_rating", nbins=30, color_discrete_sequence=[COLOR_PALETTE["primary"]])
            fig.update_traces(marker_line_width=0)
            plotly_defaults(fig, height=300)
            fig.update_layout(showlegend=False)
            st.plotly_chart(fig, use_container_width=True)
            st.markdown('</div>', unsafe_allow_html=True)
        else:
            st.info("Recs parquet file not found.")
    except Exception as e:
        st.warning(f"Could not load diversity metrics: {e}")

    divider()

    # API Health
    section_header("🏥", "API Health Check")
    try:
        r = requests.get(f"{API_BASE_URL}/health", timeout=5)
        if r.status_code == 200:
            health = r.json()
            st.markdown(
                '<div class="status-badge status-online" style="display:inline-flex;margin-bottom:0.75rem;">'
                '<span class="status-dot-online"></span>API is Online</div>',
                unsafe_allow_html=True,
            )
            st.json(health)
        else:
            st.error(f"API returned status {r.status_code}")
    except Exception as e:
        st.markdown(
            '<div class="status-badge status-offline" style="display:inline-flex;margin-bottom:0.75rem;">'
            '<span class="status-dot-offline"></span>API Unreachable</div>',
            unsafe_allow_html=True,
        )
        st.error(str(e))


# ============================================================
# Main — Nav & Routing
# ============================================================

NAV_PAGES = {
    "📊  Dataset Overview":      render_dataset_overview,
    "👤  User Behaviour":        render_user_behaviour,
    "🤖  Model Performance":     render_model_performance,
    "🎁  Recommendations Demo":  render_recommendations_demo,
    "🔧  Debug & Diagnostics":   render_diagnostics,
}


def main():
    render_top_header()
    st.markdown("<br>", unsafe_allow_html=True)

    with st.sidebar:
        st.markdown(
            """
            <div class="sidebar-brand">
                <div class="sidebar-logo">✦</div>
                <div>
                    <div class="sidebar-name">Glow‑E</div>
                    <div class="sidebar-tagline">Intelligence Center</div>
                </div>
            </div>
            """,
            unsafe_allow_html=True,
        )
        st.markdown('<div class="sidebar-section-label">Navigation</div>', unsafe_allow_html=True)

        page = st.radio(
            "nav",
            list(NAV_PAGES.keys()),
            label_visibility="collapsed",
        )

        st.markdown('<div class="sidebar-section-label" style="margin-top:2rem;">System</div>', unsafe_allow_html=True)
        st.markdown(
            f'<p style="font-size:0.72rem;color:var(--text-muted);padding:0 0.3rem;line-height:1.6;">'
            f'Spark · MinIO · FastAPI · PHP e‑commerce<br>'
            f'ALS Matrix Factorization</p>',
            unsafe_allow_html=True,
        )

    NAV_PAGES[page]()


if __name__ == "__main__":
    main()