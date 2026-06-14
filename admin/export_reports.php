<?php
// admin/export_reports.php
include('../includes/config.php');
include('../includes/auth.php');
checkLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Intelligence Reports | Fleet Command</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex">

    <?php include('include/sidebar.php'); ?>
    
    <main class="ml-64 p-8 w-full max-w-[900px] mx-auto">
        <header class="flex justify-between items-start pb-6 border-b border-slate-200 mb-8">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <div class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center font-black shadow-lg shadow-purple-500/30"><i class="fas fa-file-invoice"></i></div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Financial Intelligence Center</h1>
                </div>
                <p class="text-slate-500 text-sm font-medium">Compile and export official revenue auditing and fleet usage ledger statements.</p>
            </div>
        </header>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-2"><i class="fas fa-sliders-h text-purple-500"></i> Report Configuration</h3>
            
            <form action="generate_report_pdf.php" method="POST" target="_blank" class="space-y-6">
                <div>
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Statement Stream Template</label>
                    <select name="report_type" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl block p-3.5 font-bold outline-none focus:border-purple-500 transition-colors">
                        <option value="revenue">Deal Pipeline Revenue & Performance Statement</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Statement Start Date</label>
                        <input type="date" name="start_date" required value="<?php echo date('Y-m-01'); ?>" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl block p-3.5 font-bold outline-none focus:border-purple-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Statement End Date</label>
                        <input type="date" name="end_date" required value="<?php echo date('Y-m-t'); ?>" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl block p-3.5 font-bold outline-none focus:border-purple-500 transition-colors">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full text-white bg-purple-600 hover:bg-purple-700 font-black rounded-xl text-xs uppercase tracking-wider px-5 py-4 text-center shadow-lg shadow-purple-500/20 transition-all flex justify-center items-center gap-2">
                        <i class="fas fa-print"></i> Compile & Stream PDF Ledger
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>