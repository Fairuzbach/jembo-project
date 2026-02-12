import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';

Chart.register(ChartDataLabels);

document.addEventListener('DOMContentLoaded', function () {
    // Ambil data konfigurasi dari Blade (Global Variable)
    const config = window.gaDashboardData || {};

    // Cek apakah Chart.js sudah di-load
    if (typeof Chart === 'undefined') {
        console.error('Chart.js tidak ditemukan!');
        return;
    }

    // Helper: Hancurkan chart lama jika ada (Mencegah error Canvas reused)
    const destroyChartIfExists = (id) => {
        const chartInstance = Chart.getChart(id);
        if (chartInstance) chartInstance.destroy();
    };

    // ============================================================
    // 1. PERFORMANCE CHART (Doughnut)
    // ============================================================
    const ctxPerfId = 'performanceChart';
    const ctxPerf = document.getElementById(ctxPerfId);
    if (ctxPerf && config.performance) {
        destroyChartIfExists(ctxPerfId);
        new Chart(ctxPerf, {
            type: 'doughnut',
            data: {
                labels: ['Selesai', 'Belum Selesai'],
                datasets: [{
                    data: [config.performance.percentage, (100 - config.performance.percentage)],
                    backgroundColor: ['#FACC15', '#E2E8F0'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                cutout: '75%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': ' + context.raw + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    // ============================================================
    // HELPER UNTUK CHART STANDAR (Bar Horizontal)
    // ============================================================
    const createStandardChart = (id, type, labels, data, color, options = {}) => {
        const ctx = document.getElementById(id);
        if (!ctx) return;
        
        destroyChartIfExists(id);

        new Chart(ctx.getContext('2d'), {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total',
                    data: data,
                    backgroundColor: color,
                    borderRadius: 4,
                    barPercentage: 0.8
                }]
            },
            options: {
                indexAxis: 'x',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        color: '#fff',
                        font: { 
                            weight: 'bold',
                            size: 11
                        },
                        formatter: (val) => val > 0 ? val : '',
                        anchor: 'end',
                        align: 'start',
                        offset: 4
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            precision: 0,
                            font: { size: 10 }
                        }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            autoSkip: false,
                            font: { 
                                size: 10,
                                weight: '600'
                            }
                        }
                    }
                },
                ...options
            }
        });
    };

    // ============================================================
    // 2. CHART LOKASI
    // ============================================================
    if (config.loc) {
        createStandardChart(
            'locChart', 
            'bar', 
            config.loc.labels, 
            config.loc.values, 
            '#3b82f6'
        );
    }

    // ============================================================
    // 3. CHART DEPARTMENT
    // ============================================================
    if (config.dept) {
        createStandardChart(
            'deptChart', 
            'bar', 
            config.dept.labels, 
            config.dept.values, 
            '#8b5cf6'
        );
    }

    // ============================================================
    // 4. CHART PARAMETER (Doughnut)
    // ============================================================
    const paramId = 'paramChart';
if (document.getElementById(paramId) && config.param) {
    destroyChartIfExists(paramId);
    
    // Generate dynamic colors based on number of data points
    const generateColors = (count) => {
        const colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8',
            '#F7DC6F', '#BB8FCE', '#85C1E2', '#F8B739', '#52B788',
            '#E63946', '#A8DADC', '#457B9D', '#F1FAEE', '#E76F51',
            '#2A9D8F', '#E9C46A', '#F4A261', '#E76F51', '#264653',
            '#8338EC', '#FF006E', '#FB5607', '#FFBE0B', '#3A86FF'
        ];
        return colors.slice(0, count);
    };
    
    new Chart(document.getElementById(paramId).getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: config.param.labels,
            datasets: [{
                data: config.param.values,
                backgroundColor: generateColors(config.param.values.length),
                borderWidth: 1,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        font: {
                            size: 10
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    font: {
                        weight: 'bold',
                        size: 11
                    },
                    formatter: (value, ctx) => {
                        let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        let percentage = (value * 100 / sum).toFixed(0) + "%";
                        return value > 0 ? percentage : '';
                    }
                }
            }
        }
    });
}

    // ============================================================
    // 5. CHART BOBOT (Pie)
    // ============================================================
    const bobotId = 'bobotChart';
    if (document.getElementById(bobotId) && config.bobot) {
        destroyChartIfExists(bobotId);
        new Chart(document.getElementById(bobotId).getContext('2d'), {
            type: 'pie',
            data: {
                labels: config.bobot.labels,
                datasets: [{
                    data: config.bobot.values,
                    backgroundColor: ['#ef4444', '#f59e0b', '#22c55e'],
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            font: { size: 10 }
                        }
                    },
                    datalabels: {
                        color: '#fff',
                        font: { 
                            weight: 'bold', 
                            size: 12 
                        },
                        formatter: (value, ctx) => {
                            let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            let percentage = (value * 100 / sum).toFixed(0) + "%";
                            return value > 0 ? percentage : '';
                        }
                    }
                }
            }
        });
    }

    // ============================================================
    // CATATAN: GANTT CHART SUDAH DIPINDAH KE gantt-chart.blade.php
    // Jadi tidak perlu ada kode Gantt Chart di sini lagi
    // ============================================================
});

// ============================================================
// EXPORT PDF FUNCTION (Global)
// ============================================================
window.exportToPDF = function () {
    // 1. Cek Library
    if (typeof Swal === 'undefined' || typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
        alert('Library pendukung gagal dimuat. Pastikan SweetAlert2, html2canvas, dan jsPDF sudah di-load.');
        return;
    }

    const { jsPDF } = window.jspdf;
    // Pastikan ini ID pembungkus paling luar dari seluruh halaman dashboard Anda
    const element = document.getElementById('dashboard-content'); 
    
    if (!element) {
        alert('Element #dashboard-content tidak ditemukan!');
        return;
    }

    // 2. Persiapan Nama File
    const filterStart = document.getElementById('start_date') ? document.getElementById('start_date').value : null;
    const filterEnd = document.getElementById('end_date') ? document.getElementById('end_date').value : null;
    
    // Ambil tanggal hari ini sebagai fallback
    const todayStr = new Date().toISOString().split('T')[0];
    const startDateVal = filterStart || 'All';
    const endDateVal = filterEnd || todayStr;
    const fileName = `Laporan-Dashboard-GA-${startDateVal}_sd_${endDateVal}.pdf`;

    // 3. Tampilkan Loading
    Swal.fire({
        title: 'Memproses PDF Dashboard...',
        text: 'Sedang mengambil gambar seluruh halaman (Tabel, Statistik, dll)...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    // 4. Eksekusi html2canvas
    html2canvas(element, {
        scale: 2, // Resolusi tinggi
        useCORS: true,
        backgroundColor: '#f8fafc', // Warna background PDF
        
        // --- BAGIAN KRUSIAL (PENYELAMAT ERROR) ---
        ignoreElements: (el) => {
            // Kita HARUS mengabaikan div Gantt Chart agar tidak crash
            // Ganti 'gantt_here' dengan ID div gantt chart Anda jika berbeda
            const isGantt = el.id === 'gantt_here' || el.classList.contains('gantt-container');
            
            // Abaikan juga tombol-tombol agar PDF bersih
            const isButton = el.tagName === 'BUTTON' || el.classList.contains('no-print');

            return isGantt || isButton;
        }
        // ------------------------------------------

    }).then(canvas => {
        // 5. Konversi Canvas ke PDF
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4'); // Portrait, A4
        
        const pdfWidth = 210; // Lebar A4 dalam mm
        const pageHeight = 297; // Tinggi A4 dalam mm
        
        // Hitung tinggi gambar proporsional
        const imgHeight = canvas.height * pdfWidth / canvas.width;
        
        let heightLeft = imgHeight;
        let position = 0;

        // Halaman 1
        pdf.addImage(imgData, 'PNG', 0, position, pdfWidth, imgHeight);
        heightLeft -= pageHeight;

        // Loop untuk Halaman Berikutnya (Multi-page)
        while (heightLeft >= 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 0, position, pdfWidth, imgHeight);
            heightLeft -= pageHeight;
        }

        pdf.save(fileName);

        // 6. Konfirmasi Sukses
        Swal.fire({
            icon: 'success',
            title: 'Dashboard Berhasil Di-export!',
            html: `
                <p>File: <b>${fileName}</b> berhasil diunduh.</p>
                <hr style="margin: 10px 0;">
                <p style="font-size: 0.9em; color: #d97706;">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <b>Catatan:</b> Gantt Chart tidak disertakan dalam PDF ini untuk mencegah error sistem.
                    <br>Silakan gunakan tombol <b>"Export Gantt (Detail)"</b> untuk mengunduh jadwal secara terpisah.
                </p>
            `,
        });

    }).catch(err => {
        console.error('Error saat export PDF:', err);
        Swal.fire({ 
            icon: 'error', 
            title: 'Gagal Export', 
            text: 'Terjadi kesalahan: ' + err.message 
        });
    });
};