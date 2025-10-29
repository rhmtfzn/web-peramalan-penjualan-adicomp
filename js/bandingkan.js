// Fungsi untuk menangani tab
function openTab(tabName) {
    // Sembunyikan semua tab content
    const tabs = document.getElementsByClassName("tab-content");
    for (let i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove("active");
    }
    
    // Nonaktifkan semua tab button
    const buttons = document.getElementsByClassName("tab-button");
    for (let i = 0; i < buttons.length; i++) {
        buttons[i].classList.remove("active");
    }
    
    // Tampilkan tab yang dipilih
    document.getElementById(tabName).classList.add("active");
    
    // Aktifkan button yang dipilih
    const activeButtons = document.querySelectorAll(`button[onclick="openTab('${tabName}')"]`);
    if (activeButtons.length > 0) {
        activeButtons[0].classList.add("active");
    }
}

// Fungsi untuk konfirmasi penghapusan perbandingan
function hapusPerbandingan(id) {
    if (confirm("Apakah Anda yakin ingin menghapus data perbandingan ini?")) {
        window.location.href = "hapus_perbandingan.php?id=" + id;
    }
}

// Inisialisasi grafik perbandingan MSE jika elemen canvas ada
document.addEventListener('DOMContentLoaded', function() {
    const chartCanvas = document.getElementById('mseChart');
    
    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');
        
        // Ambil data dari elemen dengan data attribute atau dari variabel PHP yang sudah di-render
        const wmaData = parseFloat(chartCanvas.getAttribute('data-wma') || '0');
        const sesData = parseFloat(chartCanvas.getAttribute('data-ses') || '0');
        
        // Data untuk grafik
        const data = {
            labels: ['WMA', 'SES'],
            datasets: [{
                label: 'Nilai MSE (Mean Square Error)',
                data: [wmaData, sesData],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(255, 159, 64, 0.5)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        };
        
        // Konfigurasi grafik
        const config = {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Perbandingan MSE Metode WMA dan SES'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nilai MSE'
                        }
                    }
                }
            }
        };
        
        // Buat grafik
        const myChart = new Chart(ctx, config);
    }
});