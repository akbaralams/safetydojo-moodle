// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * sidebar.js
 *
 * @package   local_kopere_dashboard
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery"], function($) {
    /**
     * Return whether the viewport is using the mobile sidebar mode.
     *
     * @return {boolean}
     */
    function isMobileViewport() {
        return window.matchMedia("(max-width: 991.98px)").matches;
    }

    /**
     * Update aria-expanded for all open buttons in the shell.
     *
     * @param {HTMLElement} shell
     * @param {boolean} isOpen
     */
    function syncButtons(shell, isOpen) {
        shell.querySelectorAll("[data-kopere_dashboard-sidebar-open]").forEach(function(button) {
            button.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });
    }

    /**
     * Close the sidebar.
     *
     * @param {HTMLElement} shell
     */
    function closeSidebar(shell) {
        shell.classList.remove("is-sidebar-open");
        document.body.classList.remove("has-kopere_dashboard-sidebar-open");
        syncButtons(shell, false);
    }

    /**
     * Open the sidebar.
     *
     * @param {HTMLElement} shell
     */
    function openSidebar(shell) {
        shell.classList.add("is-sidebar-open");
        document.body.classList.add("has-kopere_dashboard-sidebar-open");
        syncButtons(shell, true);
    }

    /** @var {string} Session storage key holding the expanded/collapsed submenu state. */
    var STORAGE_KEY = "local_kopere_dashboard-submenus";

    /** @var {number} Duration of the submenu transition, must match styles.scss. */
    var TRANSITION_MS = 300;

    /**
     * Return a stable identifier for a submenu, based on its parent link.
     *
     * @param {HTMLElement} summary
     * @return {string|null}
     */
    function submenuKey(summary) {
        let link = summary.querySelector(".kopere_dashboard-card-navlink");
        return link ? link.getAttribute("href") : null;
    }

    /**
     * Read the persisted submenu state.
     *
     * @return {Object}
     */
    function readState() {
        try {
            return JSON.parse(window.sessionStorage.getItem(STORAGE_KEY)) || {};
        } catch (e) {
            return {};
        }
    }

    /**
     * Persist the state of a single submenu.
     *
     * @param {string|null} key
     * @param {boolean} isExpanded
     */
    function saveState(key, isExpanded) {
        if (!key) {
            return;
        }

        try {
            let state = readState();
            state[key] = isExpanded;
            window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {
            // Storage unavailable (private mode, quota). State simply is not remembered.
        }
    }

    /**
     * Open a submenu.
     *
     * @param {HTMLElement} summary
     * @param {HTMLElement} children
     * @param {boolean} animate
     */
    function expandSubmenu(summary, children, animate) {
        summary.classList.add("is-expanded");
        children.classList.add("is-expanded");
        summary.setAttribute("aria-expanded", "true");

        if (!animate) {
            children.style.maxHeight = "none";
            return;
        }

        children.style.maxHeight = children.scrollHeight + "px";

        // Once open, drop the fixed height so the submenu can grow freely.
        window.setTimeout(function() {
            if (children.classList.contains("is-expanded")) {
                children.style.maxHeight = "none";
            }
        }, TRANSITION_MS);
    }

    /**
     * Close a submenu.
     *
     * @param {HTMLElement} summary
     * @param {HTMLElement} children
     * @param {boolean} animate
     */
    function collapseSubmenu(summary, children, animate) {
        summary.classList.remove("is-expanded");
        summary.setAttribute("aria-expanded", "false");

        if (!animate) {
            children.classList.remove("is-expanded");
            children.style.maxHeight = "";
            return;
        }

        // Go from "none" to an explicit height first, otherwise there is nothing to animate from.
        children.style.maxHeight = children.scrollHeight + "px";

        window.requestAnimationFrame(function() {
            window.requestAnimationFrame(function() {
                children.classList.remove("is-expanded");
                children.style.maxHeight = "0px";
            });
        });
    }

    /**
     * Toggle a submenu summary item open/closed.
     *
     * @param {HTMLElement} summary
     */
    function toggleSubmenu(summary) {
        let children = summary.nextElementSibling;
        if (!children || !children.classList.contains("kopere_dashboard-card-navchildren")) {
            return;
        }

        let shouldExpand = !summary.classList.contains("is-expanded");
        if (shouldExpand) {
            expandSubmenu(summary, children, true);
        } else {
            collapseSubmenu(summary, children, true);
        }

        saveState(submenuKey(summary), shouldExpand);
    }

    /**
     * Bind the click-to-expand behaviour for submenu summary items.
     *
     * @param {HTMLElement} shell
     */
    function bindSubmenus(shell) {
        let state = readState();

        shell.querySelectorAll(".kopere_dashboard-card-navsummary").forEach(function(summary) {
            let children = summary.nextElementSibling;
            if (!children || !children.classList.contains("kopere_dashboard-card-navchildren")) {
                return;
            }

            // The server pre-expands the submenu holding the current page. A stored preference
            // for this submenu wins over that default, so the choice survives navigation.
            let key = submenuKey(summary);
            let stored = key && Object.prototype.hasOwnProperty.call(state, key) ? state[key] : null;
            let shouldExpand = stored === null ? children.classList.contains("is-expanded") : stored;

            if (shouldExpand) {
                expandSubmenu(summary, children, false);
            } else {
                collapseSubmenu(summary, children, false);
            }

            summary.addEventListener("click", function(event) {
                // Let the title link navigate normally, everything else toggles the submenu.
                if (event.target.closest(".kopere_dashboard-card-navlink")) {
                    return;
                }
                event.preventDefault();
                toggleSubmenu(summary);
            });
        });
    }

    /**
     * Bind a shell instance.
     *
     * @param {HTMLElement} shell
     */
    function bindShell(shell) {
        if (!shell) {
            return;
        }

        let openButtons = shell.querySelectorAll("[data-kopere_dashboard-sidebar-open]");
        let closeButtons = shell.querySelectorAll("[data-kopere_dashboard-sidebar-close]");
        let backdrop = shell.querySelector("[data-kopere_dashboard-sidebar-backdrop]");

        bindSubmenus(shell);

        openButtons.forEach(function(button) {
            button.addEventListener("click", function() {
                if (shell.classList.contains("is-sidebar-open")) {
                    closeSidebar(shell);
                    return;
                }

                openSidebar(shell);
            });
        });

        closeButtons.forEach(function(button) {
            button.addEventListener("click", function() {
                closeSidebar(shell);
            });
        });

        if (backdrop) {
            backdrop.addEventListener("click", function() {
                closeSidebar(shell);
            });
        }

        shell.querySelectorAll(".kopere_dashboard-card-sidenav a").forEach(function(link) {
            link.addEventListener("click", function() {
                if (isMobileViewport()) {
                    closeSidebar(shell);
                }
            });
        });

        syncButtons(shell, false);
    }

    /**
     * Initialise the responsive sidebar.
     */
    function init() {
        let shells = Array.prototype.slice.call(document.querySelectorAll("[data-kopere_dashboard-shell]"));
        if (!shells.length) {
            return;
        }

        shells.forEach(function(shell) {
            bindShell(shell);
        });

        document.addEventListener("keydown", function(event) {
            if (event.key !== "Escape") {
                return;
            }

            shells.forEach(function(shell) {
                closeSidebar(shell);
            });
        });

        $(window).on("resize", function() {
            if (isMobileViewport()) {
                return;
            }

            shells.forEach(function(shell) {
                closeSidebar(shell);
            });
        });
    }

    return {
        init: init
    };
});
