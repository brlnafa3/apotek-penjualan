DELIMITER //

DROP PROCEDURE IF EXISTS sp_proses_pembelian//
CREATE PROCEDURE sp_proses_pembelian(
    IN p_daftar_obat LONGTEXT
)

BEGIN
DECLARE v_jumlah_jenis_obat INT DEFAULT 0;
DECLARE v_indeks INT DEFAULT 0;    
DECLARE v_id_obat BIGINT DEFAULT 0;
DECLARE v_jumlah BIGINT DEFAULT 0;
DECLARE v_obat_terdaftar INT DEFAULT 0;
DECLARE v_stok_tersedia BIGINT DEFAULT 0;
DECLARE v_harga_satuan DECIMAL(10,2) DEFAULT 0;
DECLARE v_subtotal DECIMAL(20,2) DEFAULT 0;
DECLARE v_total_harga DECIMAL(20,2) DEFAULT 0;

DECLARE v_indeks_sebelumnya INT DEFAULT 0;
DECLARE v_id_obat_sebelumnya BIGINT DEFAULT 0;

DECLARE v_transaksi_aktif BOOLEAN DEFAULT FALSE;
DECLARE v_id_transaksi INT UNSIGNED DEFAULT 0;

-- Menangani kesalahan selama pemrosesan transaksi
DECLARE EXIT HANDLER FOR SQLEXCEPTION
BEGIN
IF v_transaksi_aktif = TRUE THEN
    ROLLBACK;
END IF;

RESIGNAL;
END;

-- Memastikan bahwa input berupa format JSON yang valid
IF p_daftar_obat IS NULL OR JSON_VALID(p_daftar_obat) = 0 
THEN
SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 
    'Daftar obat harus berupa JSON yang valid!';
END IF;

-- Memastikan input berupa Array
IF JSON_TYPE(p_daftar_obat) <> 'ARRAY' 
THEN 
SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 
    'Daftar obat harus berbentuk Array JSON!';
END IF;

-- Menghitung jumlah jenis obat dalam daftar
SET v_jumlah_jenis_obat = JSON_LENGTH(p_daftar_obat);

-- Memastikan daftar obat tidak kosong
IF v_jumlah_jenis_obat = 0 
THEN
SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 
    'Daftar obat tidak boleh kosong';
END IF;

-- Memulai transaksi database
START TRANSACTION;
SET v_transaksi_aktif = TRUE;

-- Membaca & Memvalidasi setiap item
WHILE v_indeks < v_jumlah_jenis_obat DO

        -- Memastikan id_obat berupa bilangan bulat
        IF COALESCE(
            JSON_TYPE(
                JSON_EXTRACT(
                    p_daftar_obat,
                    CONCAT('$[', v_indeks, '].id_obat')
                )
            ), ''
        ) <> 'INTEGER' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'Setiap obat harus memiliki id_obat berupa bilangan bulat';
        END IF;

        -- Memastikan jumlah berupa bilangan bulat
        IF COALESCE(
            JSON_TYPE(
                JSON_EXTRACT(
                    p_daftar_obat,
                    CONCAT('$[', v_indeks, '].jumlah')
                )
            ), ''
        ) <> 'INTEGER' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'Jumlah obat harus berupa bilangan bulat';
        END IF;

        -- Mengambil nilai ID obat dan jumlah
        SET v_id_obat = CAST(
            JSON_UNQUOTE(
                JSON_EXTRACT(
                    p_daftar_obat,
                    CONCAT('$[', v_indeks, '].id_obat')
                )
            ) AS SIGNED
        );

        SET v_jumlah = CAST(
            JSON_UNQUOTE(
                JSON_EXTRACT(
                    p_daftar_obat,
                    CONCAT('$[', v_indeks, '].jumlah')
                )
            ) AS SIGNED
        );

        -- ID obat harus berada dalam rentang yang valid
        IF v_id_obat <= 0
           OR v_id_obat > 4294967295 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'ID obat harus lebih dari nol dan valid';
        END IF;

        -- Jumlah pembelian harus lebih dari nol
        IF v_jumlah <= 0
           OR v_jumlah > 4294967295 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'Jumlah pembelian harus lebih dari nol dan valid';
        END IF;

        -- Memeriksa apakah ID obat sudah muncul sebelumnya (dalam 1 transaksi)
        SET v_indeks_sebelumnya = 0;

        WHILE v_indeks_sebelumnya < v_indeks DO

        SET v_id_obat_sebelumnya = CAST(
            JSON_UNQUOTE(
                JSON_EXTRACT(
                    p_daftar_obat,
                    CONCAT('$[', v_indeks_sebelumnya, '].id_obat')
                )
            ) AS SIGNED
        );

        IF v_id_obat = v_id_obat_sebelumnya THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'Obat yang sama tidak boleh dimasukkan dua kali';
        END IF;

        SET v_indeks_sebelumnya = v_indeks_sebelumnya + 1;

        END WHILE;

        -- Memeriksa apakah obat terdaftar di database
        SET v_obat_terdaftar = 0;

        SELECT COUNT(*)
        INTO v_obat_terdaftar
        FROM obat
        WHERE id_obat = v_id_obat;

        IF v_obat_terdaftar = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Obat tidak ditemukan di database';
        END IF;

        -- Mengambil stok obat yang tersedia
        SELECT stok
        INTO v_stok_tersedia
        FROM obat
        WHERE id_obat = v_id_obat
        FOR UPDATE;

        -- Memastikan stok mencukupi
        IF v_stok_tersedia < v_jumlah THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Stok obat tidak mencukupi';
        END IF;

        -- Mengambil harga satuan obat
        SELECT harga
        INTO v_harga_satuan
        FROM obat
        WHERE id_obat = v_id_obat;

        -- Menghitung subtotal obat
        SET v_subtotal = v_harga_satuan * v_jumlah;

        -- Menambahkan subtotal ke total transaksi
        SET v_total_harga = v_total_harga + v_subtotal;

        -- Beralih ke item berikutnya
        SET v_indeks = v_indeks + 1;

    END WHILE;

-- Menyimpan transaksi utama
INSERT INTO transaksi (total_harga)
VALUES (v_total_harga);

-- Mengambil ID transaksi yang baru dibuat
SET v_id_transaksi = LAST_INSERT_ID();

-- Mengulang daftar obat untuk menyimpan detail
SET v_indeks = 0;

WHILE v_indeks < v_jumlah_jenis_obat DO

    -- Mengambil ID obat
    SET v_id_obat = CAST(
        JSON_UNQUOTE(
            JSON_EXTRACT(
                p_daftar_obat,
                CONCAT('$[', v_indeks, '].id_obat')
            )
        ) AS SIGNED
    );

    -- Mengambil jumlah pembelian
    SET v_jumlah = CAST(
        JSON_UNQUOTE(
            JSON_EXTRACT(
                p_daftar_obat,
                CONCAT('$[', v_indeks, '].jumlah')
            )
        ) AS SIGNED
    );

    -- Mengambil harga satuan obat
    SELECT harga
    INTO v_harga_satuan
    FROM obat
    WHERE id_obat = v_id_obat
    FOR UPDATE;

    -- Menyimpan rincian obat ke transaksi
    INSERT INTO detail_transaksi (id_transaksi, id_obat, jumlah, harga_satuan)
    VALUES (v_id_transaksi, v_id_obat, v_jumlah, v_harga_satuan);

    -- Mengurangi stok obat
    UPDATE obat
    SET stok = stok - v_jumlah
    WHERE id_obat = v_id_obat AND stok >= v_jumlah;

    -- Memastikan stok berhasil diperbarui
    IF ROW_COUNT() <> 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Gagal memperbarui stok obat';
    END IF;

    SET v_indeks = v_indeks + 1;

END WHILE;

-- Menyelesaikan transaksi
COMMIT;
SET v_transaksi_aktif = FALSE;

-- Mengembalikan hasil kepada aplikasi
SELECT
v_id_transaksi AS id_transaksi,
v_total_harga AS total_harga,
'Transaksi berhasil' AS pesan;

END//

DELIMITER ;
