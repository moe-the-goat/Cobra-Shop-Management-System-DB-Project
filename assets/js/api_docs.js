/**
 * API Documentation JavaScript
 * Interactive features for Cobra Shop API docs
 */

class APIDocs {
    constructor() {
        this.init();
    }

    init() {
        this.setupSmoothScroll();
        this.setupActiveNavigation();
        this.setupCopyButtons();
        this.setupCollapsibleEndpoints();
        this.highlightCurrentSection();
    }

    /**
     * Smooth scroll for anchor links
     */
    setupSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = anchor.getAttribute('href').slice(1);
                const target = document.getElementById(targetId);
                
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Update URL without jump
                    history.pushState(null, '', `#${targetId}`);
                    
                    // Update active state
                    this.setActiveNav(targetId);
                }
            });
        });
    }

    /**
     * Update navigation active state on scroll
     */
    setupActiveNavigation() {
        const sections = document.querySelectorAll('.api-section');
        const navLinks = document.querySelectorAll('.sidebar-section a');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute('id');
                    this.setActiveNav(id);
                }
            });
        }, {
            rootMargin: '-100px 0px -66%',
            threshold: 0
        });
        
        sections.forEach(section => observer.observe(section));
    }

    /**
     * Set active navigation item
     */
    setActiveNav(id) {
        const navLinks = document.querySelectorAll('.sidebar-section a');
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${id}`) {
                link.classList.add('active');
            }
        });
    }

    /**
     * Highlight current section based on URL hash
     */
    highlightCurrentSection() {
        const hash = window.location.hash.slice(1);
        if (hash) {
            this.setActiveNav(hash);
            
            // Scroll to section after page load
            setTimeout(() => {
                const target = document.getElementById(hash);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 100);
        }
    }

    /**
     * Add copy buttons to code blocks
     */
    setupCopyButtons() {
        document.querySelectorAll('pre[class*="language-"]').forEach(block => {
            const wrapper = document.createElement('div');
            wrapper.className = 'code-block-wrapper';
            wrapper.style.position = 'relative';
            
            block.parentNode.insertBefore(wrapper, block);
            wrapper.appendChild(block);
            
            const copyBtn = document.createElement('button');
            copyBtn.className = 'copy-btn';
            copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
            copyBtn.setAttribute('aria-label', 'Copy code');
            copyBtn.setAttribute('title', 'Copy to clipboard');
            
            // Style the button
            Object.assign(copyBtn.style, {
                position: 'absolute',
                top: '8px',
                right: '8px',
                background: 'rgba(255, 255, 255, 0.1)',
                border: 'none',
                borderRadius: '4px',
                padding: '6px 10px',
                cursor: 'pointer',
                color: '#a0a0a0',
                fontSize: '14px',
                transition: 'all 0.2s',
                opacity: '0'
            });
            
            wrapper.appendChild(copyBtn);
            
            // Show on hover
            wrapper.addEventListener('mouseenter', () => {
                copyBtn.style.opacity = '1';
            });
            
            wrapper.addEventListener('mouseleave', () => {
                copyBtn.style.opacity = '0';
            });
            
            // Copy functionality
            copyBtn.addEventListener('click', async () => {
                const code = block.querySelector('code').textContent;
                
                try {
                    await navigator.clipboard.writeText(code);
                    copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                    copyBtn.style.color = '#10b981';
                    
                    setTimeout(() => {
                        copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
                        copyBtn.style.color = '#a0a0a0';
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy:', err);
                    copyBtn.innerHTML = '<i class="fas fa-times"></i>';
                    copyBtn.style.color = '#ef4444';
                    
                    setTimeout(() => {
                        copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
                        copyBtn.style.color = '#a0a0a0';
                    }, 2000);
                }
            });
        });
    }

    /**
     * Setup collapsible endpoint bodies
     */
    setupCollapsibleEndpoints() {
        document.querySelectorAll('.endpoint-header').forEach(header => {
            const body = header.nextElementSibling;
            if (!body || !body.classList.contains('endpoint-body')) return;
            
            // Add toggle indicator
            const toggle = document.createElement('span');
            toggle.className = 'endpoint-toggle';
            toggle.innerHTML = '<i class="fas fa-chevron-down"></i>';
            toggle.style.cssText = `
                margin-left: 10px;
                color: #6b7280;
                transition: transform 0.3s;
                cursor: pointer;
            `;
            header.appendChild(toggle);
            
            // Make header clickable
            header.style.cursor = 'pointer';
            
            header.addEventListener('click', () => {
                const isExpanded = body.style.display !== 'none';
                
                if (isExpanded) {
                    body.style.display = 'none';
                    toggle.querySelector('i').style.transform = 'rotate(-90deg)';
                } else {
                    body.style.display = 'block';
                    toggle.querySelector('i').style.transform = 'rotate(0deg)';
                }
            });
        });
    }
}

// Search functionality
class APISearch {
    constructor() {
        this.setupSearch();
    }

    setupSearch() {
        // Create search input
        const searchContainer = document.createElement('div');
        searchContainer.className = 'search-container';
        searchContainer.innerHTML = `
            <div class="search-wrapper" style="padding: 15px;">
                <input type="search" 
                       id="api-search" 
                       placeholder="Search endpoints..." 
                       aria-label="Search API documentation"
                       style="width: 100%; padding: 10px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem;">
            </div>
        `;
        
        const sidebar = document.querySelector('.sidebar-nav');
        if (sidebar) {
            sidebar.insertBefore(searchContainer, sidebar.firstChild);
        }
        
        const searchInput = document.getElementById('api-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
        }
    }

    handleSearch(query) {
        const sections = document.querySelectorAll('.sidebar-section');
        const normalizedQuery = query.toLowerCase().trim();
        
        sections.forEach(section => {
            const links = section.querySelectorAll('a');
            let hasVisibleLinks = false;
            
            links.forEach(link => {
                const text = link.textContent.toLowerCase();
                const href = link.getAttribute('href').toLowerCase();
                const matches = text.includes(normalizedQuery) || href.includes(normalizedQuery);
                
                link.parentElement.style.display = matches || !normalizedQuery ? 'block' : 'none';
                if (matches) hasVisibleLinks = true;
            });
            
            // Hide section if no visible links
            section.style.display = hasVisibleLinks || !normalizedQuery ? 'block' : 'none';
        });
    }
}

// Try it out functionality (interactive API testing)
class APITester {
    constructor() {
        this.setupTryItOut();
    }

    setupTryItOut() {
        document.querySelectorAll('.endpoint').forEach(endpoint => {
            const header = endpoint.querySelector('.endpoint-header');
            const method = header.querySelector('.method')?.textContent.toUpperCase();
            const path = header.querySelector('.path')?.textContent;
            
            if (!method || !path) return;
            
            // Only add for GET requests (safe to test)
            if (method !== 'GET') return;
            
            const body = endpoint.querySelector('.endpoint-body');
            if (!body) return;
            
            const tryBtn = document.createElement('button');
            tryBtn.className = 'try-it-btn';
            tryBtn.innerHTML = '<i class="fas fa-play"></i> Try it';
            tryBtn.style.cssText = `
                margin-top: 20px;
                padding: 10px 20px;
                background: #4361ee;
                color: white;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-size: 0.9rem;
                font-weight: 600;
                transition: background 0.2s;
            `;
            
            tryBtn.addEventListener('mouseenter', () => {
                tryBtn.style.background = '#3651d4';
            });
            
            tryBtn.addEventListener('mouseleave', () => {
                tryBtn.style.background = '#4361ee';
            });
            
            tryBtn.addEventListener('click', () => this.executeRequest(endpoint, method, path));
            body.appendChild(tryBtn);
        });
    }

    async executeRequest(endpoint, method, path) {
        const body = endpoint.querySelector('.endpoint-body');
        
        // Remove any existing response
        const existingResponse = body.querySelector('.try-response');
        if (existingResponse) existingResponse.remove();
        
        // Create response container
        const responseContainer = document.createElement('div');
        responseContainer.className = 'try-response';
        responseContainer.style.cssText = `
            margin-top: 20px;
            padding: 15px;
            background: #1e1e2e;
            border-radius: 8px;
        `;
        responseContainer.innerHTML = '<p style="color: #a0a0a0;">Loading...</p>';
        body.appendChild(responseContainer);
        
        try {
            const response = await fetch(path);
            const data = await response.json();
            
            responseContainer.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="color: ${response.ok ? '#10b981' : '#ef4444'}; font-weight: 600;">
                        Status: ${response.status}
                    </span>
                    <button onclick="this.closest('.try-response').remove()" 
                            style="background: none; border: none; color: #a0a0a0; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <pre style="margin: 0; overflow-x: auto;"><code class="language-json">${JSON.stringify(data, null, 2)}</code></pre>
            `;
            
            // Re-highlight
            if (window.Prism) {
                Prism.highlightAllUnder(responseContainer);
            }
        } catch (error) {
            responseContainer.innerHTML = `
                <div style="color: #ef4444;">
                    <p><strong>Error:</strong> ${error.message}</p>
                    <p style="font-size: 0.85rem; color: #a0a0a0; margin-top: 10px;">
                        Make sure the server is running and CORS is properly configured.
                    </p>
                </div>
            `;
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new APIDocs();
    new APISearch();
    new APITester();
});





