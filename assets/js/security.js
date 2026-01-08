/**
 * Security Helper Functions for Cobra Shop
 * Handles CSRF tokens, session management, and secure API calls
 * 
 * @package CobraShop
 * @version 1.0.0
 */

const CobraSecurity = (function() {
    'use strict';
    
    // Configuration
    const config = {
        csrfTokenEndpoint: 'get-csrf-token.php',
        sessionCheckEndpoint: 'check-session.php',
        logoutEndpoint: 'logout.php',
        csrfTokenName: 'csrf_token',
        tokenRefreshInterval: 30 * 60 * 1000, // 30 minutes
        sessionCheckInterval: 5 * 60 * 1000   // 5 minutes
    };
    
    // State
    let csrfToken = null;
    let tokenRefreshTimer = null;
    let sessionCheckTimer = null;
    
    /**
     * Fetch CSRF token from server
     * @returns {Promise<string>}
     */
    async function fetchCsrfToken() {
        try {
            const response = await fetch(config.csrfTokenEndpoint, {
                method: 'GET',
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error('Failed to fetch CSRF token');
            }
            
            const data = await response.json();
            csrfToken = data.csrf_token;
            
            // Store in sessionStorage for persistence across page loads
            sessionStorage.setItem(config.csrfTokenName, csrfToken);
            
            return csrfToken;
        } catch (error) {
            console.error('Error fetching CSRF token:', error);
            throw error;
        }
    }
    
    /**
     * Get current CSRF token (fetch if not available)
     * @returns {Promise<string>}
     */
    async function getCsrfToken() {
        if (csrfToken) {
            return csrfToken;
        }
        
        // Try to get from sessionStorage
        const storedToken = sessionStorage.getItem(config.csrfTokenName);
        if (storedToken) {
            csrfToken = storedToken;
            return csrfToken;
        }
        
        // Fetch new token
        return await fetchCsrfToken();
    }
    
    /**
     * Make a secure API call with CSRF token
     * @param {string} url - API endpoint
     * @param {Object} options - Fetch options
     * @returns {Promise<Response>}
     */
    async function secureApiCall(url, options = {}) {
        try {
            // Get CSRF token
            const token = await getCsrfToken();
            
            // Default options
            const defaultOptions = {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            };
            
            // Merge options
            const mergedOptions = {
                ...defaultOptions,
                ...options,
                headers: {
                    ...defaultOptions.headers,
                    ...(options.headers || {})
                }
            };
            
            // Add CSRF token to body if it's JSON
            if (mergedOptions.body && typeof mergedOptions.body === 'string') {
                try {
                    const bodyData = JSON.parse(mergedOptions.body);
                    bodyData[config.csrfTokenName] = token;
                    mergedOptions.body = JSON.stringify(bodyData);
                } catch (e) {
                    // Body is not JSON, skip
                }
            }
            
            const response = await fetch(url, mergedOptions);
            
            // Handle CSRF token expiry
            if (response.status === 403) {
                const data = await response.clone().json();
                if (data.message && data.message.includes('security token')) {
                    // Refresh token and retry
                    await fetchCsrfToken();
                    return secureApiCall(url, options);
                }
            }
            
            return response;
        } catch (error) {
            console.error('Secure API call failed:', error);
            throw error;
        }
    }
    
    /**
     * Check if user is logged in
     * @returns {Promise<Object>}
     */
    async function checkSession() {
        try {
            const response = await fetch(config.sessionCheckEndpoint, {
                method: 'GET',
                credentials: 'include'
            });
            
            const data = await response.json();
            
            // Dispatch event for session status
            window.dispatchEvent(new CustomEvent('sessionStatusChanged', {
                detail: {
                    loggedIn: data.logged_in,
                    user: data.user
                }
            }));
            
            return data;
        } catch (error) {
            console.error('Session check failed:', error);
            return { logged_in: false, user: null };
        }
    }
    
    /**
     * Logout user
     * @returns {Promise<Object>}
     */
    async function logout() {
        try {
            const response = await secureApiCall(config.logoutEndpoint);
            const data = await response.json();
            
            // Clear local storage
            localStorage.removeItem('user');
            sessionStorage.removeItem(config.csrfTokenName);
            csrfToken = null;
            
            // Dispatch logout event
            window.dispatchEvent(new CustomEvent('userLoggedOut'));
            
            return data;
        } catch (error) {
            console.error('Logout failed:', error);
            throw error;
        }
    }
    
    /**
     * Secure login function
     * @param {string} email
     * @param {string} password
     * @returns {Promise<Object>}
     */
    async function login(email, password) {
        try {
            const response = await secureApiCall('login_secure.php', {
                method: 'POST',
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                // Store user data
                localStorage.setItem('user', JSON.stringify(data.data.user));
                
                // Update CSRF token if provided
                if (data.data.csrf_token) {
                    csrfToken = data.data.csrf_token;
                    sessionStorage.setItem(config.csrfTokenName, csrfToken);
                }
                
                // Dispatch login event
                window.dispatchEvent(new CustomEvent('userLoggedIn', {
                    detail: { user: data.data.user }
                }));
            }
            
            return data;
        } catch (error) {
            console.error('Login failed:', error);
            throw error;
        }
    }
    
    /**
     * Secure registration function
     * @param {Object} userData
     * @returns {Promise<Object>}
     */
    async function register(userData) {
        try {
            const response = await secureApiCall('register_secure.php', {
                method: 'POST',
                body: JSON.stringify(userData)
            });
            
            return await response.json();
        } catch (error) {
            console.error('Registration failed:', error);
            throw error;
        }
    }
    
    /**
     * Start automatic token refresh
     */
    function startTokenRefresh() {
        if (tokenRefreshTimer) {
            clearInterval(tokenRefreshTimer);
        }
        
        tokenRefreshTimer = setInterval(async () => {
            try {
                await fetchCsrfToken();
            } catch (error) {
                console.error('Token refresh failed:', error);
            }
        }, config.tokenRefreshInterval);
    }
    
    /**
     * Start session status checking
     */
    function startSessionCheck() {
        if (sessionCheckTimer) {
            clearInterval(sessionCheckTimer);
        }
        
        sessionCheckTimer = setInterval(checkSession, config.sessionCheckInterval);
    }
    
    /**
     * Initialize security module
     */
    async function init() {
        try {
            // Fetch initial CSRF token
            await fetchCsrfToken();
            
            // Check session status
            await checkSession();
            
            // Start automatic refresh
            startTokenRefresh();
            startSessionCheck();
            
            console.log('Security module initialized');
        } catch (error) {
            console.error('Security initialization failed:', error);
        }
    }
    
    /**
     * Get stored user data
     * @returns {Object|null}
     */
    function getStoredUser() {
        try {
            const userData = localStorage.getItem('user');
            return userData ? JSON.parse(userData) : null;
        } catch (e) {
            return null;
        }
    }
    
    // Public API
    return {
        init,
        getCsrfToken,
        fetchCsrfToken,
        secureApiCall,
        checkSession,
        login,
        logout,
        register,
        getStoredUser
    };
})();

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    CobraSecurity.init();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CobraSecurity;
}





