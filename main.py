# -*- coding: utf-8 -*-
import sys, time, json
from PyQt5 import QtCore, QtGui, QtWidgets
from playwright.sync_api import sync_playwright
import json, os


import os, traceback, datetime

# --- Playwright tarayıcı yolu ve exe uyumu ---
import sys, os, glob

import sys, os, glob, subprocess, platform

def _exchook(exc_type, exc, tb):
    import traceback
    log("UNHANDLED:\n" + "".join(traceback.format_exception(exc_type, exc, tb)))
sys.excepthook = _exchook


def _is_frozen():
    return getattr(sys, "frozen", False)

def _install_playwright_chromium_silently():
    # EXE içinde kurulum YAPMAYIN
    if _is_frozen():
        log("Frozen exe: otomatik Chromium kurulumu atlandı. ms-playwright paketlenmeli.")
        return False
    try:
        log("Chromium indiriliyor (dev) ...")
        cmd = [sys.executable, "-m", "playwright", "install", "chromium"]
        if platform.system() != "Windows":
            cmd.append("--with-deps")
        r = subprocess.run(cmd, capture_output=True, text=True, check=True)
        log("Chromium kuruldu: " + (r.stdout or "").strip())
        return True
    except Exception as e:
        log("Chromium kurulamadı (dev): " + str(e))
        return False


def _base_dir():
    return os.path.dirname(sys.executable) if _is_frozen() \
           else os.path.dirname(os.path.abspath(__file__))

def _ms_pw_dir():
    # exe: dist\AltinWidget\ms-playwright
    cand = os.path.join(_base_dir(), "ms-playwright")
    if os.path.isdir(cand):
        return cand
    # dev: proje kökünde olabilir
    cand2 = os.path.join(os.path.dirname(_base_dir()), "ms-playwright")
    return cand2 if os.path.isdir(cand2) else None

def _chromium_exe():
    root = _ms_pw_dir()
    if not root:
        return None
    for pat in (
        os.path.join(root, "chromium-*", "chrome-win", "chrome.exe"),
        os.path.join(root, "chromium-*", "chrome-win", "chrome-win", "chrome.exe"),
    ):
        hits = glob.glob(pat)
        if hits:
            return hits[0]
    return None


# Ortam değişkeni (Playwright kendi tarayıcı klasörünü buradan okur)
mp = _ms_pw_dir()
if mp:
    os.environ.setdefault("PLAYWRIGHT_BROWSERS_PATH", mp)


def log(msg):
    try:
        base = os.path.dirname(sys.executable) if getattr(sys, "frozen", False) \
               else os.path.dirname(os.path.abspath(__file__))
        fp = os.path.join(base, "AltinWidget_log.txt")
        ts = time.strftime("%Y-%m-%d %H:%M:%S")
        with open(fp, "a", encoding="utf-8") as f:
            f.write(f"[{ts}] {msg}\n")
    except Exception:
        pass

def save_prices_to_json(mode, alis, satis):
    data = {"mode": mode, "alis": alis, "satis": satis}
    path = r"C:\xampp\htdocs\dianora_siparis\altin.json"
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)



URL = "https://saglamoglualtin.com/"
UPDATE_SEC = 60               # ekrana basma periyodu
PREFETCH_BEFORE_SEC = 10      # ekrana basmadan önce arka plan çekme süresi (sonraki döngüler için)
SPLASH_MIN_MS = 3000          # splash minimum süresi (ms) – veri geç gelirse daha uzun da kalır

MODES = ["gold", "usd", "eur"]
MODE_NAMES = {"gold": "HAS ALTIN", "usd": "USD / DOLAR", "eur": "EUR / EURO"}

def tr_number(x: float, nd=3):
    s = f"{x:,.{nd}f}"
    return s.replace(",", "X").replace(".", ",").replace("X", ".")

def _clean_num(s: str):
    if not s: return None
    s = s.replace("₺", "").replace("%", "").strip()
    try:
        if s.count(",") <= 1 and s.count(".") >= 1:
            return float(s.replace(",", ""))
        return float(s.replace(".", "").replace(",", "."))
    except Exception:
        try: return float(s.replace(",", "."))
        except Exception: return None

# ---------- Playwright çekim (tek shot) ----------
def fetch_all_once(timeout_ms=15000):
    """Tek shot: Genel->HAS ALTIN, Döviz->USD/TL & EUR/TL alış/satış"""
    def click_tab(page, text):
        try:
            page.get_by_text(text, exact=False).first.click(timeout=2000)
            return True
        except:
            return page.evaluate(r"""
                (name)=>{
                  const sel='[role="tab"], .nav a, .nav-link, a, button, li, div, span';
                  const els=[...document.querySelectorAll(sel)];
                  for(const e of els){
                    const t=(e.innerText||'').trim().toUpperCase();
                    if(t.includes(String(name).toUpperCase())){ e.click(); return true; }
                  }
                  return false;
                }
            """, text)

    EXTRACT_JS = r"""
    (keys) => {
      function isVisible(el){
        if(!el) return false;
        const st = window.getComputedStyle(el);
        return st && st.display !== 'none' && st.visibility !== 'hidden' && el.offsetParent !== null;
      }
      function normName(t){
        if(!t) return '';
        let s = String(t).toUpperCase().trim();
        s = s.split('%')[0]; s = s.replace(/\s+/g, ' ').trim();
        return s;
      }
      function headerIdx(table){
        const res = { alis: -1, satis: -1 };
        const thead = table.querySelector('thead'); if(!thead) return res;
        const ths = Array.from(thead.querySelectorAll('th')).map(x => normName(x.innerText));
        res.alis  = ths.findIndex(t => t.includes('ALIŞ'));
        res.satis = ths.findIndex(t => t.includes('SATIŞ'));
        return res;
      }
      function pickNum(t){
        if(!t) return null;
        const s = String(t).trim();
        if (s.includes('%')) return null;
        if (/^\d{1,2}:\d{2}:\d{2}$/.test(s)) return null;
        const m = s.match(/-?\d{1,3}(?:[\.,]\d{3})*(?:[\.,]\d+)?/);
        return m ? m[0] : null;
      }
      const tables = Array.from(document.querySelectorAll('table')); if(!tables.length) return null;
      for (const table of tables){
        if (!isVisible(table)) continue;
        const idx = headerIdx(table);
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        for (const tr of rows){
          const cells = Array.from(tr.querySelectorAll('th,td')); if(!cells.length) continue;
          const name = normName(cells[0].innerText);
          const exact = keys.some(k => normName(k) === name); if(!exact) continue;
          let alis=null, satis=null;
          if (idx.alis  >=0 && idx.alis  < cells.length) alis  = pickNum(cells[idx.alis].innerText);
          if (idx.satis >=0 && idx.satis < cells.length) satis = pickNum(cells[idx.satis].innerText);
          if (!alis || !satis){
            const found=[]; for (const c of cells){ const n = pickNum(c.innerText); if (n) found.push(n); }
            if (found.length >= 2){ if(!alis) alis=found[0]; if(!satis) satis=found[1]; }
          }
          if (alis && satis) return { name, alis, satis };
        }
      }
      return null;
    }
    """
    out = {"gold": (None,None), "usd": (None,None), "eur": (None,None)}
    with sync_playwright() as p:
        exe_path = _chromium_exe()
        launch_kwargs = dict(headless=True)
        if exe_path and os.path.isfile(exe_path):
            log("Chromium launch denemesi...")
            launch_kwargs["executable_path"] = exe_path   # <-- kritik

        br = p.chromium.launch(**launch_kwargs)
        page = br.new_page()
        page.goto(URL, wait_until="domcontentloaded", timeout=timeout_ms)
        page.wait_for_timeout(1200)
        # Genel -> HAS ALTIN
        click_tab(page, "S.Piyasa Genel"); page.wait_for_timeout(800)
        d = page.evaluate(EXTRACT_JS, ["HAS ALTIN"])
        if d: out["gold"] = (_clean_num(d["alis"]), _clean_num(d["satis"]))
        # Döviz -> USD/TL, EUR/TL
        click_tab(page, "S.Piyasa Döviz"); page.wait_for_timeout(1500)
        d_usd = page.evaluate(EXTRACT_JS, ["USD/TL", "USD/TRY"])
        d_eur = page.evaluate(EXTRACT_JS, ["EUR/TL", "EUR/TRY"])
        if d_usd: out["usd"] = (_clean_num(d_usd["alis"]), _clean_num(d_usd["satis"]))
        if d_eur: out["eur"] = (_clean_num(d_eur["alis"]), _clean_num(d_eur["satis"]))
        br.close()
    return out

# ---------- PyQt (splash: veri gelene kadar; sonra 50/60 döngü) ----------
class FetchThread(QtCore.QThread):
    fetched = QtCore.pyqtSignal(object)  # dict or {"error": ...}
    def run(self):
        try:
            data = fetch_all_once()
            self.fetched.emit(data)
        except Exception as e:
            self.fetched.emit({"error": str(e)})

class Widget(QtWidgets.QWidget):
    pricesUpdated = QtCore.pyqtSignal(str, float, float)

    def __init__(self):
        super().__init__()
        self.setWindowFlags(QtCore.Qt.FramelessWindowHint | QtCore.Qt.Tool | QtCore.Qt.WindowStaysOnTopHint)
        self.setAttribute(QtCore.Qt.WA_TranslucentBackground)

        self.mode_idx = 0
        self.mode = MODES[self.mode_idx]
        self.cache = {"gold": (None,None), "usd": (None,None), "eur": (None,None)}

        self.label = QtWidgets.QLabel("MÖRF YAZILIM")
        self.label.setStyleSheet(
            "QLabel { background: rgba(28,28,30,0.92); color:#fff;"
            "font: 12pt 'Segoe UI'; padding:10px 14px; border-radius:12px; }"
        )
        lay = QtWidgets.QVBoxLayout(self); lay.setContentsMargins(0,0,0,0); lay.addWidget(self.label)

        # Mouse (drag + tek tık mod değiştir)
        self._drag = None; self._press_pos = None; self._press_time = None
        self.label.mousePressEvent   = self._on_press
        self.label.mouseMoveEvent    = self._on_move
        self.label.mouseReleaseEvent = self._on_release

        # Timers: prefetch & update (döngü için)
        self.t_prefetch = QtCore.QTimer(self); self.t_prefetch.setSingleShot(True)
        self.t_update   = QtCore.QTimer(self); self.t_update.setSingleShot(True)
        self.t_prefetch.timeout.connect(self._do_prefetch_cycle)
        self.t_update.timeout.connect(self._do_update_cycle)

        # Splash zamanlayıcı (minimum süreyi takip etmek için)
        self._splash_timer = QtCore.QElapsedTimer(); self._splash_timer.start()
        self._first_fetch_done = False

        # Konum
        self.restore_position()

        # Splash gösterilirken ilk prefetch'i hemen başlat
        self._do_prefetch_initial()

    # --- Kalıcı konum ---
    def restore_position(self):
        s = QtCore.QSettings()
        x = s.value("win/x", type=int); y = s.value("win/y", type=int)
        if x is not None and y is not None: self.move(x, y)
        else: self._pos_bottom_right()
    def save_position(self):
        p = self.frameGeometry().topLeft(); s = QtCore.QSettings()
        s.setValue("win/x", p.x()); s.setValue("win/y", p.y())
    def closeEvent(self, e):
        self.save_position(); super().closeEvent(e)

    def _pos_bottom_right(self):
        self.adjustSize()
        g = QtWidgets.QApplication.primaryScreen().availableGeometry()
        self.move(g.right() - self.width() - 20, g.bottom() - self.height() - 40)

    # --- Mouse (drag + click) ---
    def _on_press(self, e):
        self._drag = e.globalPos() - self.frameGeometry().topLeft()
        self._press_pos = e.globalPos(); self._press_time = time.time()
    def _on_move(self, e):
        if self._drag: self.move(e.globalPos() - self._drag); self.save_position()
    def _on_release(self, e):
        if self._press_pos is not None:
            dist = e.globalPos() - self._press_pos
            moved = abs(dist.x()) + abs(dist.y())
            elapsed = time.time() - (self._press_time or time.time())
            if moved < 5 and elapsed < 0.3: self.cycle_mode()
        self._drag = None; self._press_pos = None; self._press_time = None

    def cycle_mode(self):
        self.mode_idx = (self.mode_idx + 1) % len(MODES)
        self.mode = MODES[self.mode_idx]
        # splash bittiğinde (ilk veri gelmeden) mod değişirse bile splash kalacak;
        # ilk veri gelince aktif moda göre render edilir.
        if self._first_fetch_done:
            self.render_current()

    # ---------- İlk prefetch & splash bitirme ----------
    def _do_prefetch_initial(self):
        th = FetchThread()
        th.fetched.connect(self._on_fetched_initial)
        th.start()
        self._th0 = th

    @QtCore.pyqtSlot(object)
    def _on_fetched_initial(self, data):
        if isinstance(data, dict) and "error" in data:
            # hata olursa splash kalır; 5 sn sonra tekrar dene
            QtCore.QTimer.singleShot(5000, self._do_prefetch_initial)
            return
        self.cache.update(data)
        elapsed = self._splash_timer.elapsed()
        remain = max(0, SPLASH_MIN_MS - elapsed)
        QtCore.QTimer.singleShot(remain, self._finish_splash_and_show)

    def _finish_splash_and_show(self):
        self._first_fetch_done = True
        # İlk ekrana basış: aktif moda göre
        self.render_current()
        # Sonraki döngüler: 50. sn prefetch -> 60. sn ekrana bas
        self._schedule_next_cycle()

    # ---------- Döngü (prefetch & update) ----------
    def _schedule_next_cycle(self):
        self.t_prefetch.start(max(0, (UPDATE_SEC - PREFETCH_BEFORE_SEC) * 1000))  # 50.sn
        self.t_update.start(UPDATE_SEC * 1000)                                    # 60.sn

    def _do_prefetch_cycle(self):
        th = FetchThread()
        th.fetched.connect(self._on_prefetched_cycle)
        th.start()
        self._th = th

    @QtCore.pyqtSlot(object)
    def _on_prefetched_cycle(self, data):
        if isinstance(data, dict) and "error" in data:
            return
        self.cache.update(data)

    def _do_update_cycle(self):
        self.render_current()
        self._schedule_next_cycle()

    # ---------- Görselleştirme ----------
    def render_current(self):
        alis, satis = self.cache.get(self.mode, (None, None))
        if alis is None or satis is None:
            return  # veri yoksa mevcut yazıyı/splash'ı koru
        now = time.strftime("%H:%M")
        self.label.setText(f"{MODE_NAMES[self.mode]}\nAlış: {tr_number(alis)} ₺  •  Satış: {tr_number(satis)} ₺\n{now}")
        self.pricesUpdated.emit(self.mode, alis, satis)
        try:
            save_prices_to_json(self.mode, alis, satis)
        except Exception as e:
            print("JSON kaydetme hatası:", e)

class App(QtWidgets.QApplication):
    def __init__(self, argv):
        super().__init__(argv)
        self.setQuitOnLastWindowClosed(False)

        # tray
        self.tray = QtWidgets.QSystemTrayIcon(self)
        icon = self.style().standardIcon(QtWidgets.QStyle.SP_ComputerIcon)
        self.tray.setIcon(icon)
        self.tray.setToolTip("MÖRF YAZILIM")
        self.tray.setVisible(True)

        menu = QtWidgets.QMenu()
        act_show = menu.addAction("Göster / Gizle")
        act_refresh = menu.addAction("Şimdi prefetch (10 sn sonra göster)")
        menu.addSeparator()
        act_quit = menu.addAction("Çık")
        self.tray.setContextMenu(menu)

        act_show.triggered.connect(self.toggle_widget)
        act_refresh.triggered.connect(self.manual_refresh)
        act_quit.triggered.connect(self.quit)
        self.tray.activated.connect(self._onTrayActivated)

        self.w = Widget()
        self.w.pricesUpdated.connect(self.on_prices)

    def _onTrayActivated(self, reason):
        if reason == QtWidgets.QSystemTrayIcon.Trigger:
            self.toggle_widget()

    def toggle_widget(self):
        self.w.setVisible(not self.w.isVisible())

    def manual_refresh(self):
        # anlık prefetch – 10 sn sonra ekrana bas (splash çalmışsa fark etmez)
        self.w._do_prefetch_cycle()
        QtCore.QTimer.singleShot(PREFETCH_BEFORE_SEC * 1000, self.w.render_current)

    @QtCore.pyqtSlot(str, float, float)
    def on_prices(self, mode, alis, satis):
        name = MODE_NAMES.get(mode, mode.upper())
        self.tray.setToolTip(f"{name}\nAlış: {tr_number(alis)} ₺ • Satış: {tr_number(satis)} ₺")



if __name__ == "__main__":
    QtCore.QCoreApplication.setOrganizationName("Dianora")
    QtCore.QCoreApplication.setApplicationName("AltinWidget")
    app = App(sys.argv)
    app.w.show()
    sys.exit(app.exec_())
