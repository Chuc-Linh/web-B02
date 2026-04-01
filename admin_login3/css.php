<?php
  // Dòng này cực kỳ quan trọng, không có là trình duyệt không nhận
  header("Content-type: text/css; charset: UTF-8");
?>
        body {
            margin: 0;
            font-family: Arial;
            background: #f4f6f8;
        }

        
                /* Container bao bọc tất cả các cụm nhập liệu */
        .form-row {
            display: flex;       /* Kích hoạt nằm ngang */
            flex-wrap: wrap;  /* Ép các phần tử không được xuống dòng */
            gap: 15px;           /* Khoảng cách giữa các ô */
            background: #fff;
            padding: 20px;
            align-items: flex-end; /* Giúp các ô và nút bấm thẳng hàng chân với nhau */
        }

        /* Từng cụm bao gồm Label và Input */
        .input-group {
            display: flex;
            flex-direction: column; /* Label ở trên, Input ở dưới */
            flex: 1;                /* Tùy chọn: Giúp các ô có độ dài bằng nhau */
        }

        .input-group label {
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 13px;
        }
        .sidebar {
            width: 220px;
            height: 100vh;
            background: #2c3e50;
            color: white;
            position: fixed;
            padding: 20px;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            color: white;
            padding: 12px;
            text-decoration: none;
            margin-bottom: 5px;
        }

        .sidebar a:hover {
            background: #34495e;
        }

        .content {
            margin-left: 240px;
            padding: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #3498db;
            color: white;
        }

        h1 {
            margin-bottom: 20px;
        }
        .btn {padding: 8px 15px; text-decoration: none; color: white; border-radius: 3px; font-size: 12px; margin: 2px; display: inline-block; border: none; cursor: pointer; }
        .btn-lock { background: #e74c3c; }
        .btn-unlock { background: #2ecc71;  }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: white; margin: 10% auto; padding: 20px; border-radius: 8px; width: 60%; position: relative; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .close { position: absolute; right: 15px; top: 10px; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: red; }
        #modal-body table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        #modal-body th { background: #34495e; color: white; }
        /* Container cho bộ lọc */
        .filter-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            border: 1px solid #e1e4e8;
        }

        .filter-title {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 13px;
            color: #5a67d8;
            font-weight: 600;
        }

        .filter-input {
            padding: 10px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            outline: none;
            transition: border 0.3s;
            min-width: 180px;
        }

        .filter-input:focus {
            border-color: #3498db;
        }

        .btn-filter {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
            text-decoration:none;
        }

        .badge-hoantat {
        background: #89a3b4;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: bold;
        display: inline-block;
        
    }
        .btn-filter:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }

        .btn-clear {
            background: #edf2f7;
            color: #4a5568;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 13px;
        }
        .btn {
            display: inline-block;
            padding: 6px 12px;
            margin-bottom: 0;
            font-size: 14px;
            text-align: center;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
            border: 1px solid transparent;
            color: #fff;
            font-weight: normal;
        }
        .btn-success { background-color: #28a745; border-color: #28a745; }
        .btn-primary { background-color: #007bff; border-color: #007bff; }
        .btn-danger { background-color: #dc3545; border-color: #dc3545; }
        .btn-secondary { background-color: #6c757d; border-color: #6c757d; }
        .btn-sm { padding: 4px 8px; font-size: 12px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #343a40; color: white; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .badge { padding: 4px 8px; border-radius: 10px; font-size: 11px; color: white; }
        .bg-success { background-color: #28a745; }
        .bg-secondary { background-color: #6c757d; }
        .input-error {
        border: 2px solid #dc3545 !important;
        background-color: #fff8f8;
}
       
        
        