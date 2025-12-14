// Test JavaScript syntax
let livewireComponent = null;
let chartInstances = {
    payslipStatusPie: null,
    departmentComparison: null,
    monthlyTrends: null,
    payslipLine: null,
    payslipBar: null
};

const translations = {
    no_email_failures_found: "No email failures found",
    no_sms_failures_found: "No SMS failures found",
    no_encryption_failures_found: "No encryption failures found"
};

function destroyCharts() {
    if (chartInstances.payslipStatusPie) chartInstances.payslipStatusPie.destroy();
    if (chartInstances.departmentComparison) chartInstances.departmentComparison.destroy();
    if (chartInstances.monthlyTrends) chartInstances.monthlyTrends.destroy();
    if (chartInstances.payslipLine) {
        document.querySelector('.line-chart').innerHTML = '';
        chartInstances.payslipLine = null;
    }
    if (chartInstances.payslipBar) {
        document.querySelector('.bar-chart').innerHTML = '';
        chartInstances.payslipBar = null;
    }
}

function recreateCharts() {
    destroyCharts();
    fetchFreshChartData();
}

function fetchFreshChartData() {
    if (typeof Livewire === 'undefined') {
        setTimeout(fetchFreshChartData, 100);
        return;
    }

    const livewireElement = document.querySelector('[wire\\:id]');
    if (livewireElement) {
        livewireComponent = { call: function() { return { then: function() {} }; } };
        if (livewireComponent) {
            livewireComponent.call('getChartData').then((chartData) => {
                createChartsWithData(chartData);
            });
        } else {
            createChartsWithStaticData();
        }
    } else {
        createChartsWithStaticData();
    }
}

function createChartsWithStaticData() {
    // Mock implementation
}

function createChartsWithData(chartData) {
    // Mock implementation
    if (chartData.payslip_status_pie_chart) {
        chartInstances.payslipStatusPie = { destroy: function() {} };
    }
    if (chartData.department_comparison) {
        chartInstances.departmentComparison = { destroy: function() {} };
    }
    if (chartData.monthly_trends) {
        chartInstances.monthlyTrends = { destroy: function() {} };
    }
    if (chartData.chart_data && chartData.chart_daily) {
        chartInstances.payslipLine = { destroy: function() {} };
        chartInstances.payslipBar = { destroy: function() {} };
    }
}

function addChartDrillDown(chartInstance, chartType) {
    if (!chartInstance) return;
    chartInstance.on('draw', function(data) {
        if (data.type === 'line' || data.type === 'bar' || data.type === 'point') {
            data.element._node.addEventListener('click', function() {
                const seriesName = data.series.name;
                if (seriesName === 'failed' || seriesName === 'encryption_issues') {
                    showFailureDetailsModal(seriesName);
                }
            });
            data.element._node.style.cursor = 'pointer';
        }
    });
}

function showFailureDetailsModal(failureType) {
    if (!livewireComponent) {
        console.error('Livewire component not available');
        return;
    }
    livewireComponent.call('getFailureDetails', failureType).then((details) => {
        populateFailureModal(details);
        const modal = { show: function() {} };
        modal.show();
    });
}

function populateFailureModal(details) {
    const emailList = { innerHTML: '' };
    if (details.email_failures && details.email_failures.length > 0) {
        emailList.innerHTML = details.email_failures.map(failure =>
            `<div class="mb-2 p-2 bg-light rounded small">
                <strong>${failure.employee_name}</strong><br>
                <span class="text-muted">${failure.email}</span><br>
                <span class="text-danger">${failure.error_message}</span>
            </div>`
        ).join('');
    } else {
        emailList.innerHTML = '<div class="text-muted">' + translations.no_email_failures_found + '</div>';
    }

    const smsList = { innerHTML: '' };
    if (details.sms_failures && details.sms_failures.length > 0) {
        smsList.innerHTML = details.sms_failures.map(failure =>
            `<div class="mb-2 p-2 bg-light rounded small">
                <strong>${failure.employee_name}</strong><br>
                <span class="text-muted">${failure.phone}</span><br>
                <span class="text-danger">${failure.error_message}</span>
            </div>`
        ).join('');
    } else {
        smsList.innerHTML = '<div class="text-muted">' + translations.no_sms_failures_found + '</div>';
    }

    const encryptionList = { innerHTML: '' };
    if (details.encryption_failures && details.encryption_failures.length > 0) {
        encryptionList.innerHTML = details.encryption_failures.map(failure =>
            `<div class="mb-2 p-2 bg-light rounded small">
                <strong>${failure.employee_name}</strong><br>
                <span class="text-muted">${failure.matricule}</span><br>
                <span class="text-danger">${failure.error_message}</span>
            </div>`
        ).join('');
    } else {
        encryptionList.innerHTML = '<div class="text-muted">' + translations.no_encryption_failures_found + '</div>';
    }
}

console.log('JavaScript syntax test completed successfully');