// /modules/processes_tasks/js/organigrama-level-interactions.js
// Enhanced level interaction functionality for better UX - SIMPLIFIED VERSION
console.log('[Processes & Tasks] ORGANIGRAMA LEVEL INTERACTIONS JS loaded');

(function(){
  document.addEventListener('DOMContentLoaded', ()=>{
    const canvas = document.getElementById('org-canvas');
    if (!canvas) return;

    // Add CSS for the placement indicator animation
    if (!document.querySelector('#organigrama-level-interactions-styles')) {
      const style = document.createElement('style');
      style.id = 'organigrama-level-interactions-styles';
      style.textContent = `
        @keyframes fade-in-out {
          0% { opacity: 0; transform: scale(0.8); }
          30% { opacity: 1; transform: scale(1.1); }
          70% { opacity: 1; transform: scale(1); }
          100% { opacity: 0; transform: scale(0.9); }
        }
        
        .placement-indicator {
          animation: fade-in-out 2s ease-in-out;
        }
        
        .placement-indicator::before {
          content: "Zona de nivel";
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: var(--bs-success);
          color: white;
          padding: 4px 8px;
          border-radius: 12px;
          font-size: 0.75rem;
          font-weight: 600;
          white-space: nowrap;
        }
      `;
      document.head.appendChild(style);
    }

    // Clean up any existing tooltips
    function cleanupTooltips() {
      document.querySelectorAll('.level-tooltip').forEach(tooltip => tooltip.remove());
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        if (el._ixTooltip) {
          try { el._ixTooltip.dispose(); } catch(_) {}
          delete el._ixTooltip;
        }
      });
    }

    // Initialize cleanup
    cleanupTooltips();
    
    // Clean up on tab change or page unload
    document.addEventListener('visibilitychange', cleanupTooltips);
    window.addEventListener('beforeunload', cleanupTooltips);
  });
})();