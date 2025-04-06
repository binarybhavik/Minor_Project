// Timetable management class
class TimetableManager {
    constructor() {
        this.timetable = [];
        this.form = document.getElementById('timetableForm');
        this.container = document.getElementById('timetableContainer');
        this.searchBtn = document.querySelector('.search-btn');
        this.resetBtn = document.querySelector('.reset-btn');
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }
        if (this.resetBtn) {
            this.resetBtn.addEventListener('click', () => this.resetForm());
        }
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        
        // Show loading state
        this.setLoadingState(true);
        
        try {
            // Get form data
            const formData = new FormData(this.form);
            const searchParams = new URLSearchParams(formData);
            
            // Add action parameter
            searchParams.append('action', 'Timetable');
            
            // Make the AJAX request
            const response = await fetch(`controller.php?${searchParams.toString()}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.timetable = data.timetable;
                this.displayTimetable();
            } else {
                this.showError(data.message || 'Failed to fetch timetable');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showError('An error occurred while fetching the timetable');
        } finally {
            this.setLoadingState(false);
        }
    }

    setLoadingState(isLoading) {
        if (this.searchBtn) {
            this.searchBtn.disabled = isLoading;
            this.searchBtn.innerHTML = isLoading ? 
                '<span class="spinner"></span> Loading...' : 
                'Search Timetable';
        }
    }

    displayTimetable() {
        if (!this.container) return;

        if (!this.timetable || this.timetable.length === 0) {
            this.container.innerHTML = '<p class="no-data">No timetable entries found</p>';
            return;
        }

        // Group entries by day
        const groupedEntries = this.groupEntriesByDay();
        
        // Create table for each day
        const tablesHtml = Object.entries(groupedEntries).map(([day, entries]) => `
            <div class="day-section">
                <h3>${day}</h3>
                <table class="timetable-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Subject</th>
                            <th>Faculty</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${entries.map(entry => `
                            <tr>
                                <td>${entry.timeStart} - ${entry.timeEnd}</td>
                                <td>${entry.subjectName} (${entry.subjectCode})</td>
                                <td>${entry.facultyName}</td>
                                <td>${entry.roomNumber}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `).join('');

        this.container.innerHTML = tablesHtml;
    }

    groupEntriesByDay() {
        return this.timetable.reduce((groups, entry) => {
            if (!groups[entry.day]) {
                groups[entry.day] = [];
            }
            groups[entry.day].push(entry);
            return groups;
        }, {});
    }

    showError(message) {
        if (this.container) {
            this.container.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    ${message}
                </div>`;
        }
    }

    resetForm() {
        if (this.form) {
            this.form.reset();
            if (this.container) {
                this.container.innerHTML = '';
            }
        }
    }
}

// Initialize the timetable manager when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.timetableManager = new TimetableManager();
}); 