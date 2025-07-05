<div class="live-users">
    <div class="live-indicator">
        <div class="live-dot"></div>
        <div class="ripple"></div>
    </div>
    <div class="counter-content">
        <span class="user-count" data-count="{{ $count }}">{{ $count }}</span>
        <span class="user-text">active now</span>
    </div>
</div>

<style>
.live-users {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 8px 16px;
    background: rgba(52, 168, 83, 0.1);
    border-radius: 30px;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    cursor: pointer;
    border: 1px solid transparent;
}

.live-users:hover {
    background: rgba(52, 168, 83, 0.15);
    transform: translateY(-2px) scale(1.02);
    border-color: rgba(52, 168, 83, 0.2);
    box-shadow: 0 4px 12px rgba(52, 168, 83, 0.15);
}

.live-indicator {
    position: relative;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.live-dot {
    width: 8px;
    height: 8px;
    background: #34A853;
    border-radius: 50%;
    position: relative;
    box-shadow: 0 0 10px rgba(52, 168, 83, 0.5);
    animation: glow 2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

.ripple {
    position: absolute;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid #34A853;
    animation: ripple 2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

.counter-content {
    display: flex;
    align-items: baseline;
    gap: 6px;
}

.user-count {
    font-weight: 600;
    font-size: 1rem;
    color: #34A853;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    display: inline-block;
}

.user-text {
    font-size: 0.9rem;
    color: #666;
    font-weight: 500;
    transition: all 0.3s ease;
}

.live-users:hover .user-text {
    color: #34A853;
}

/* Enhanced Animations */
@keyframes glow {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
        box-shadow: 0 0 10px rgba(52, 168, 83, 0.5);
    }
    50% {
        transform: scale(1.2);
        opacity: 0.8;
        box-shadow: 0 0 20px rgba(52, 168, 83, 0.8);
    }
}

@keyframes ripple {
    0% {
        transform: scale(0.8);
        opacity: 0.8;
        border-width: 2px;
    }
    50% {
        border-width: 1px;
    }
    100% {
        transform: scale(1.8);
        opacity: 0;
        border-width: 0px;
    }
}

/* Smooth number change animation */
.user-count.updating {
    animation: numberUpdate 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes numberUpdate {
    0% {
        transform: scale(1) translateY(0);
        opacity: 1;
    }
    50% {
        transform: scale(0.8) translateY(-10px);
        opacity: 0;
    }
    51% {
        transform: scale(0.8) translateY(10px);
        opacity: 0;
    }
    100% {
        transform: scale(1) translateY(0);
        opacity: 1;
    }
}

/* Add multiple ripples for more dynamic effect */
.live-indicator::before,
.live-indicator::after {
    content: '';
    position: absolute;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 1px solid rgba(52, 168, 83, 0.3);
    animation: ripple 2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

.live-indicator::before {
    animation-delay: -0.5s;
}

.live-indicator::after {
    animation-delay: -1s;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userCount = document.querySelector('.user-count');
    const liveUsers = document.querySelector('.live-users');
    let currentCount = parseInt(userCount.textContent);

    // Add hover effect for the ripple
    liveUsers.addEventListener('mouseenter', function() {
        const ripple = this.querySelector('.ripple');
        ripple.style.animationDuration = '1s';
    });

    liveUsers.addEventListener('mouseleave', function() {
        const ripple = this.querySelector('.ripple');
        ripple.style.animationDuration = '2s';
    });

    // Smooth counting animation function
    function animateValue(start, end, duration) {
        if (start === end) return;
        
        const range = end - start;
        const increment = end > start ? 1 : -1;
        const stepTime = Math.abs(Math.floor(duration / range));
        let current = start;
        
        const timer = setInterval(function() {
            current += increment;
            userCount.textContent = current;
            
            if (current === end) {
                clearInterval(timer);
                userCount.classList.remove('updating');
            }
        }, stepTime);
    }

    // Update counter with smooth animation
    function updateCounter(newCount) {
        if (newCount !== currentCount) {
            userCount.classList.add('updating');
            animateValue(currentCount, newCount, 500); // 500ms duration
            currentCount = newCount;
        }
    }

    // Fetch updates every 30 seconds
    setInterval(function() {
        fetch('/api/active-users')
            .then(response => response.json())
            .then(data => {
                updateCounter(data.count);
            });
    }, 30000);
});
</script> 