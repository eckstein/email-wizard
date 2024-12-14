/**
 * Modal Utility
 * 
 * Creates and manages modal dialogs with consistent styling and behavior
 */

import '../../../css/components/_modal.css';

const ANIMATION_DURATION = 200;

export class WizModal {
    constructor(options = {}) {
        this.options = {
            title: '',
            content: '',
            width: '600px',
            maxHeight: '90vh',
            showClose: true,
            className: '',
            onClose: null,
            footer: null,
            ...options
        };

        this.element = null;
        this.isOpen = false;
        this.boundHandleEscape = this.handleEscape.bind(this);
        this._createModal();
    }

    _createModal() {
        // Remove any existing modals with the same class
        if (this.options.className) {
            document.querySelectorAll(`.wiz-modal.${this.options.className}`).forEach(modal => {
                modal.remove();
            });
        }

        // Create modal container
        this.element = document.createElement('div');
        this.element.className = 'wiz-modal' + (this.options.className ? ` ${this.options.className}` : '');
        
        // Create modal content
        const modalContent = document.createElement('div');
        modalContent.className = 'wiz-modal-content';
        modalContent.style.width = this.options.width;
        modalContent.style.maxHeight = this.options.maxHeight;

        // Add title if provided
        if (this.options.title) {
            const titleBar = document.createElement('div');
            titleBar.className = 'wiz-modal-header';
            
            const title = document.createElement('h2');
            title.className = 'wiz-modal-title';
            title.textContent = this.options.title;
            titleBar.appendChild(title);

            if (this.options.showClose) {
                const closeBtn = document.createElement('button');
                closeBtn.className = 'wiz-modal-close wizard-button small button-text';
                closeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                closeBtn.addEventListener('click', () => this.close());
                titleBar.appendChild(closeBtn);
            }

            modalContent.appendChild(titleBar);
        }

        // Add content
        const contentContainer = document.createElement('div');
        contentContainer.className = 'wiz-modal-body';
        if (typeof this.options.content === 'string') {
            contentContainer.innerHTML = this.options.content;
        } else if (this.options.content instanceof Node) {
            contentContainer.appendChild(this.options.content);
        }
        modalContent.appendChild(contentContainer);

        // Add footer if provided
        if (this.options.footer) {
            const footerContainer = document.createElement('div');
            footerContainer.className = 'wiz-modal-footer';
            if (typeof this.options.footer === 'string') {
                footerContainer.innerHTML = this.options.footer;
            } else if (this.options.footer instanceof Node) {
                footerContainer.appendChild(this.options.footer);
            }
            modalContent.appendChild(footerContainer);
        }

        // Add to modal
        this.element.appendChild(modalContent);

        // Add click outside to close
        this.element.addEventListener('click', (e) => {
            if (e.target === this.element) {
                this.close();
            }
        });
    }

    handleEscape(e) {
        if (e.key === 'Escape' && this.isOpen) {
            this.close();
        }
    }

    open() {
        if (this.isOpen) return;

        // Add to DOM
        document.body.appendChild(this.element);
        document.body.style.overflow = 'hidden';

        // Add escape key handler
        document.addEventListener('keydown', this.boundHandleEscape);

        // Force reflow to ensure transition works
        this.element.offsetHeight;

        // Add inline styles for debugging
        this.element.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        `;

        // Trigger animation
        requestAnimationFrame(() => {
            this.element.classList.add('open');
        });

        this.isOpen = true;
    }

    close() {
        if (!this.isOpen) return;

        this.element.classList.remove('open');

        // Remove escape key handler
        document.removeEventListener('keydown', this.boundHandleEscape);

        // Wait for animation
        setTimeout(() => {
            if (this.element && this.element.parentNode) {
                document.body.removeChild(this.element);
            }
            document.body.style.overflow = '';
            
            if (this.options.onClose) {
                this.options.onClose();
            }
        }, ANIMATION_DURATION);

        this.isOpen = false;
    }

    setContent(content) {
        const contentContainer = this.element.querySelector('.wiz-modal-body');
        if (contentContainer) {
            contentContainer.innerHTML = '';
            if (typeof content === 'string') {
                contentContainer.innerHTML = content;
            } else if (content instanceof Node) {
                contentContainer.appendChild(content);
            }
        }
    }

    static async showModal(options) {
        const modal = new WizModal(options);
        modal.open();
        return modal;
    }
} 