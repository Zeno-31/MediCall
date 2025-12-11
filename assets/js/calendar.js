// Simple calendar implementation
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;
    
    let currentDate = new Date();
    
    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = firstDay.getDay();
        
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                          'July', 'August', 'September', 'October', 'November', 'December'];
        
        let html = `
            <div class="calendar-header">
                <button onclick="previousMonth()" class="btn btn-sm">← Previous</button>
                <h3>${monthNames[month]} ${year}</h3>
                <button onclick="nextMonth()" class="btn btn-sm">Next →</button>
            </div>
            <div class="calendar-grid">
                <div style="text-align: center; font-weight: bold; padding: 8px;">Sun</div>
                <div style="text-align: center; font-weight: bold; padding: 8px;">Mon</div>
                <div style="text-align: center; font-weight: bold; padding: 8px;">Tue</div>
                <div style="text-align: center; font-weight: bold; padding: 8px;">Wed</div>
                <div style="text-align: center; font-weight: bold; padding: 8px;">Thu</div>
                <div style="text-align: center; font-weight: bold; padding: 8px;">Fri</div>
                <div style="text-align: center; font-weight: bold; padding: 8px;">Sat</div>
        `;
        
        // Empty cells before first day
        for (let i = 0; i < startingDayOfWeek; i++) {
            html += '<div></div>';
        }
        
        // Days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const hasEvent = events.some(e => e.start.startsWith(dateStr));
            
            let eventsList = '';
            if (hasEvent) {
                const dayEvents = events.filter(e => e.start.startsWith(dateStr));
                eventsList = dayEvents.map(e => `<div style="font-size: 11px; background: #2563eb; color: white; padding: 2px 4px; margin-top: 4px; border-radius: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${e.title}</div>`).join('');
            }
            
            html += `
                <div class="calendar-day ${hasEvent ? 'has-event' : ''}">
                    <div style="font-weight: bold;">${day}</div>
                    ${eventsList}
                </div>
            `;
        }
        
        html += '</div>';
        calendarEl.innerHTML = html;
    }
    
    window.previousMonth = function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    };
    
    window.nextMonth = function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    };
    
    renderCalendar();
});
