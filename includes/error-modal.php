<!-- Error Modal – include once per page, trigger via showErrorModal(title, message) -->
<div id="errorModal" class="erp-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="errorModalTitle">
  <div class="erp-modal-box">
    <div class="erp-modal-icon-wrap">
      <div class="erp-modal-icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
    </div>
    <div class="erp-modal-body">
      <h5 id="errorModalTitle" class="erp-modal-title">Something went wrong</h5>
      <p id="errorModalMessage" class="erp-modal-message">An unexpected error occurred. Please try again.</p>
    </div>
    <button class="erp-modal-close" onclick="closeErrorModal()" aria-label="Close">
      <i class="fas fa-times"></i>
    </button>
    <div class="erp-modal-footer">
      <button class="erp-modal-btn" onclick="closeErrorModal()">Got it</button>
    </div>
  </div>
</div>

<style>
  .erp-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(23, 36, 61, 0.45);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    animation: erpFadeIn 0.2s ease;
  }
  .erp-modal-overlay.active {
    display: flex;
  }
  .erp-modal-box {
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: 0 24px 60px rgba(23, 36, 61, 0.18), 0 0 0 1px rgba(13, 110, 253, 0.08);
    padding: 32px 28px 24px;
    width: 100%;
    max-width: 420px;
    position: relative;
    animation: erpSlideUp 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    border-top: 4px solid var(--danger);
  }
  .erp-modal-icon-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 16px;
  }
  .erp-modal-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #fdecef;
    color: var(--danger);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
  }
  .erp-modal-body {
    text-align: center;
    margin-bottom: 20px;
  }
  .erp-modal-title {
    color: var(--ink-900);
    font-weight: 800;
    font-size: 1.1rem;
    margin: 0 0 8px;
    font-family: 'Manrope', sans-serif;
  }
  .erp-modal-message {
    color: var(--ink-500);
    font-size: 0.88rem;
    font-weight: 500;
    margin: 0;
    line-height: 1.6;
    font-family: 'Manrope', sans-serif;
  }
  .erp-modal-close {
    position: absolute;
    top: 14px;
    right: 14px;
    background: var(--bg-soft);
    border: 1px solid #dce7f3;
    border-radius: 8px;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ink-500);
    cursor: pointer;
    font-size: 0.75rem;
    transition: all 0.15s ease;
  }
  .erp-modal-close:hover {
    background: #fdecef;
    border-color: var(--danger);
    color: var(--danger);
  }
  .erp-modal-footer {
    display: flex;
    justify-content: center;
  }
  .erp-modal-btn {
    background: var(--danger);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 10px 32px;
    font-weight: 700;
    font-size: 0.88rem;
    font-family: 'Manrope', sans-serif;
    cursor: pointer;
    box-shadow: 0 6px 16px rgba(214, 69, 69, 0.28);
    transition: all 0.2s ease;
  }
  .erp-modal-btn:hover {
    background: #c53c3c;
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(214, 69, 69, 0.35);
  }
  @keyframes erpFadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
  }
  @keyframes erpSlideUp {
    from { opacity: 0; transform: translateY(24px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
  }
  @media (max-width: 480px) {
    .erp-modal-box { margin: 16px; padding: 24px 18px 20px; }
  }
</style>

<script>
  function showErrorModal(title, message) {
    document.getElementById('errorModalTitle').textContent   = title   || 'Something went wrong';
    document.getElementById('errorModalMessage').textContent = message || 'An unexpected error occurred. Please try again.';
    document.getElementById('errorModal').classList.add('active');
  }
  function closeErrorModal() {
    document.getElementById('errorModal').classList.remove('active');
  }
  // Close on backdrop click
  document.getElementById('errorModal').addEventListener('click', function(e) {
    if (e.target === this) closeErrorModal();
  });
  // Close on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeErrorModal();
  });
</script>
