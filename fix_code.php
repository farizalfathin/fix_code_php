<?php
require_once('Connections/conPsm.php');
require_once('Connections/conSel.php');
require_once('Connections/conLec.php');

date_default_timezone_set('Asia/Kuala_Lumpur');
$tarikh = getTarikhMasa('Y-m-d');
$masa = getTarikhMasa('H:i:s');
$tahun = getTarikhMasa('Y');
$staf = $_SESSION['MM_Username'];
$idstaf = $_SESSION['MM_nostaf'];
$no_kes = Es::cape($_GET['no_kes']);

$array_status = [
    '2' => 'SEDANG DIBAIKI',
    '1' => 'SELESAI',
    '0' => 'ADUAN TIDAK JELAS, SILA KEMASKINI SEMULA ADUAN'
];

if (isset($_GET['no_kes']) && $_GET['no_kes'] != '') {

    $no_kes = Es::cape($_GET['no_kes']);

    $query_edit = "SELECT aduan_kerosakan.id, 
            aduan_kerosakan.no_kes, 
            aduan_kerosakan.no_pengadu, 
            aduan_kerosakan.kod_bangunan, 
            aduan_kerosakan.lokasi_aduan, 
            aduan_kerosakan.kod_rosak, 
            aduan_kerosakan.jenis_rosak, 
            aduan_kerosakan.komen, 
            jenis_kerosakan.nama_rosak, 
            nama_bangunan.Nama_Bangunan AS nama_bangunan 
        FROM cms_sql.aduan_kerosakan 
        LEFT JOIN cms_select.jenis_kerosakan ON jenis_kerosakan.kod_rosak = aduan_kerosakan.kod_rosak 
        LEFT JOIN cms_select.nama_bangunan ON aduan_kerosakan.KOD_BANGUNAN = nama_bangunan.Kod_Bangunan 
        WHERE aduan_kerosakan.kategori = '1' 
            AND aduan_kerosakan.no_kes = '$no_kes' 
        LIMIT 1";
    $result_edit = mysql_query($query_edit, $conLec) or die(mysql_error());
    $total_edit = mysql_num_rows($result_edit);
    $row_edit = mysql_fetch_assoc($result_edit);

    if ($total_edit == 0) {
        $flashMsg->add('e', 'Data tidak sah!', getUrlGet(1, ['slug', 'no_kes']).'&slug=pentadbiran-aduan-semak-aduan');
    } 
    else {
        if (isset($_POST['btnUpdate'])) {

            $post_status_baiki = (isset($_POST['status_baiki']) && $_POST['status_baiki'] != '' ?  Es::cape($_POST['status_baiki']) : '');
            $edit_status_baiki = ($post_status_baiki != '' ? "'$post_status_baiki'" : "NULL");

            $post_catatan = (isset($_POST['catatan']) && $_POST['catatan'] != '' ?  Es::cape($_POST['catatan']) : '');
            $replace_catatan = ($post_catatan != '' ? str_replace(array("\r\n", "\r", "\n"), '<<>>', $post_catatan) : '');
            $toupper_catatan = ($replace_catatan != '' ? strtoupper($replace_catatan) : '');
            $edit_catatan = ($toupper_catatan != '' ? "'$toupper_catatan'" : "NULL");

            $edit_no_kes = "'$no_kes'";
            $edit_idstaf = "'$idstaf'";
            $edit_tarikh = "'$tarikh'";
            $edit_masa = "'$masa'";

            // === Proses upload file jika ada ===
            if (isset($_FILES['nama_fail']) && $_FILES['nama_fail']['error'] == 0) {
                $fileTmpPath = $_FILES['nama_fail']['tmp_name'];
                $fileName = $_FILES['nama_fail']['name'];
                $fileSize = $_FILES['nama_fail']['size'];
                $fileType = $_FILES['nama_fail']['type'];

                $tmp = explode(".", $fileName);
                $fileExtension = strtolower(end($tmp));

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];

                if (!in_array($fileExtension, $allowedExtensions)) {
                    $flashMsg->add('e', 'Jenis fail tidak dibenarkan! (Hanya JPG, PNG, PDF)');
                } elseif ($fileSize > 3 * 1024 * 1024) {
                    $flashMsg->add('e', 'Saiz fail melebihi 3MB!');
                } else {
                    $uploadpath = '../../media/aduan';
                    if (!is_dir($uploadpath)) {
                        mkdir($uploadpath, 0777, true);
                    }

                    $newFileName = uniqid() . '.' . $fileExtension;
                    $dest_path = $uploadpath . '/' . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        // Bisa simpan path ke database di sini jika diperlukan
                    } else {
                        $flashMsg->add('e', 'Gagal upload fail!');
                    }
                }
            }

            if ($post_status_baiki == '' && $post_catatan == '') {
                $flashMsg->add('e', 'Sila isi kesemua bahagian yang wajib diisi!', getUrlGet());
            } 
            else {
                $update_aduan = "UPDATE cms_sql.aduan_kerosakan 
                    SET aduan_kerosakan.status_baiki = $edit_status_baiki, 
                        aduan_kerosakan.catatan = $edit_catatan, 
                        aduan_kerosakan.no_pembaiki = $edit_idstaf, 
                        aduan_kerosakan.tarikh_baiki = $edit_tarikh, 
                        aduan_kerosakan.masa_baiki = $edit_masa 
                    WHERE aduan_kerosakan.no_kes = $edit_no_kes";
                mysql_query($update_aduan, $conLec) or die(mysql_error());

                $flashMsg->add('s', 'Data berjaya diproses.', getUrlGet(1, ['slug', 'no_kes']).'&slug=pentadbiran-aduan-semak-aduan');
            }
        }
    }
}

// Ambil detail aduan
$query_aduan = "SELECT aduan_kerosakan.id, 
        aduan_kerosakan.no_kes AS nombor_kes, 
        aduan_kerosakan.no_pengadu AS nombor_pengadu, 
        aduan_kerosakan.no_hp AS nombor_telefon, 
        aduan_kerosakan.catatan, 
        aduan_kerosakan.tahun, 
        aduan_kerosakan.unit, 
        aduan_kerosakan.jawatan, 
        aduan_kerosakan.tarikh_aduan, 
        aduan_kerosakan.masa_aduan, 
        aduan_kerosakan.kod_bangunan, 
        aduan_kerosakan.lokasi_aduan, 
        aduan_kerosakan.kod_rosak, 
        aduan_kerosakan.jenis_rosak, 
        aduan_kerosakan.komen, 
        aduan_kerosakan.status_baiki, 
        staf_peribadi.nama AS nama_pengadu, 
        jenis_kerosakan.nama_rosak, 
        nama_bangunan.Nama_Bangunan AS nama_bangunan, 
        sp.nama AS nama_pembaiki 
    FROM cms_sql.aduan_kerosakan 
    INNER JOIN cms_psm.staf_peribadi ON staf_peribadi.nostaf = aduan_kerosakan.no_pengadu 
    LEFT JOIN cms_select.jenis_kerosakan ON jenis_kerosakan.kod_rosak = aduan_kerosakan.kod_rosak 
    LEFT JOIN cms_select.nama_bangunan ON aduan_kerosakan.KOD_BANGUNAN = nama_bangunan.Kod_Bangunan 
    LEFT JOIN cms_psm.staf_peribadi AS sp ON sp.nostaf = aduan_kerosakan.no_pembaiki 
    WHERE aduan_kerosakan.no_kes = '$no_kes' 
    LIMIT 1";
$result_aduan = mysql_query($query_aduan, $conLec) or die(mysql_error());
$row_aduan = mysql_fetch_assoc($result_aduan);

function getLampiran($no_kes, $idstaf) {
    $query_getlampiran = "SELECT kerosakan_upload.* 
        FROM cms_sql.kerosakan_upload
        WHERE kerosakan_upload.no_kes = '$no_kes' 
            AND kerosakan_upload.no_pengadu = '$idstaf'";
    $result_getlampiran = mysql_query($query_getlampiran) or die(mysql_error());
    return $result_getlampiran;
}

function Lampiran($no_kes, $idstaf) {
    $query_lampiran = "SELECT kerosakan_baiki_upload.*
        FROM cms_sql.kerosakan_baiki_upload
        WHERE kerosakan_baiki_upload.no_kes = '$no_kes'
            AND kerosakan_baiki_upload.no_pengadu = '$idstaf'";
    $result_lampiran = mysql_query($query_lampiran) or die(mysql_error());
    return $result_lampiran;
}

include_once('base.php');
?>

<script type="text/javascript">
function muatnaik_fail() {
    var muatnaik = $('#nama_fail');
    var kira = muatnaik[0].files.length;

    for (var i = 0; i < kira; i++) {

        var saiz = muatnaik[0].files[i].size;
        var nama = muatnaik[0].files[i].name;
        var jpg = /\.jpe?g$/i.test(nama);
        var png = /\.png$/i.test(nama);
        var pdf = /\.pdf$/i.test(nama);

        if (!jpg && !png && !pdf) {

            alert('Hanya jpg, png dan pdf fail sahaja dibenarkan dimuatnaik!');
            var muatnaik = '';
            var nama = '';
            break;
        } 
        else if (saiz > 3100000) {

            alert('Maksimum saiz fail yang dibenarkan muatnaik adalah 3mb!');
            var muatnaik = '';
            var nama = '';
            break;
            }
        }
    }

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnUpdate'])) {

    $id_aduan = $_POST['id_aduan'];
    $tajuk = $_POST['tajuk'];
    $kategori = $_POST['kategori'];
    $keterangan = $_POST['keterangan'];
    $tarikh_aduan = $_POST['tarikh_aduan'];
    $status_aduan = $_POST['status_aduan'];

    $uploadpath = "../../media/aduan"; // folder tujuan upload

    // --- Proses Upload File ---
    $uploadedFileName = null;

    if (isset($_FILES['nama_fail']) && $_FILES['nama_fail']['error'] == 0) {
        $fileTmpPath = $_FILES['nama_fail']['tmp_name'];
        $fileName = $_FILES['nama_fail']['name'];
        $fileSize = $_FILES['nama_fail']['size'];
        $fileType = $_FILES['nama_fail']['type'];

        // Ambil ekstensi file
        $tmp = explode(".", $fileName);
        $fileExtension = strtolower(end($tmp));

        // Tentukan ekstensi yang diizinkan
        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Nama file baru unik
            $newFileName = uniqid('img_', true) . '.' . $fileExtension;

            // Pastikan folder tujuan ada
            if (!is_dir($uploadpath)) {
                mkdir($uploadpath, 0777, true);
            }

            $dest_path = $uploadpath . '/' . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $uploadedFileName = $newFileName;
            } else {
                echo "<script>alert('Gagal memindahkan file ke folder tujuan.');</script>";
            }
        } else {
            echo "<script>alert('Jenis file tidak diizinkan. Hanya JPG, JPEG, PNG, GIF.');</script>";
        }
    }

    // --- Query Update ---
    $sql = "UPDATE aduan 
            SET tajuk = ?, kategori = ?, keterangan = ?, tarikh_aduan = ?, status_aduan = ?" .
            ($uploadedFileName ? ", nama_fail = ?" : "") .
            " WHERE id_aduan = ?";

    $stmt = $conn->prepare($sql);

    if ($uploadedFileName) {
        $stmt->bind_param("ssssssi", $tajuk, $kategori, $keterangan, $tarikh_aduan, $status_aduan, $uploadedFileName, $id_aduan);
    } else {
        $stmt->bind_param("sssssi", $tajuk, $kategori, $keterangan, $tarikh_aduan, $status_aduan, $id_aduan);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Aduan berhasil diperbarui.'); window.location.href='senarai_aduan.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui aduan.');</script>";
    }
}
?>

<?php startblock('script'); ?> 
<?php superblock(); ?>
<script src="js/all/custom.js" type="text/javascript"></script>

<script type="text/javascript">
$(document).ready(function() {
    $('#borang_aduan').submit(function() {
        if ($('#status_baiki').val() == '') {

            alert('Sila pilih Status Aduan!');
            $('#status_baiki').focus();
            return false;
        }

        if ($('#catatan').val() == '') {

            alert('Sila isi Catatan!');
            $('#catatan').focus();
            return false;
        }

        return true;
    });
});
</script>

<?php endblock(); ?>


<?php startblock('style'); ?>
<?php superblock(); ?>
<link href="css/all/custom.css" type="text/css" rel="stylesheet">
<?php endblock(); ?>

<?php startblock('content'); ?>
<?php superblock(); ?>
<div class="panel-group">
    <div class="panel panel-ekias">
        <div class="panel-heading">
            <h5 class="panel-ekias-title">KEMASKINI ADUAN</h5>
        </div>

        <div class="panel-body">
            <table class="table table-bordered">
                <tr>
                    <td class="text-left va-mid">
                        <b class="text-danger">Peringatan :</b><br>
                        1. Bahagian yang bertanda (<b class="text-danger">*</b>) adalah wajib dimasukkan maklumat.<br>
                    </td>
                </tr>
            </table>

            <form method="post" id="borang_aduan" enctype="multipart/form-data">
                <?= get_form() ?>
                <table class="table table-striped table-responsive table-bordered table-bottom">
                    <thead>
                        <tr style="background-color:#999;">
                            <th colspan="3" class="text-left va-mid">
                                BORANG KEMASKINI ADUAN
                            </th>
                        </tr>
                    </thead>

                    <tr>
                        <th width="20%" class="text-left va-mid">NOMBOR KES</th>
                        <td width="2%" class="text-left va-mid">:</td>
                        <td width="78%" class="text-left va-mid">
                            <?= ($row_aduan['nombor_kes'] != '' ? $row_aduan['nombor_kes'] : '-') ?>
                        </td>
                    </tr>

                    <tr>
                        <th class="text-left va-mid">NAMA PENGADU</th>
                        <td class="text-left va-mid">:</td>
                        <td class="text-left va-mid">
                            <?= ($row_aduan['nama_pengadu'] != '' ? $row_aduan['nama_pengadu'] : '-') ?>
                        </td>
                    </tr>

                    <tr>
                        <th class="text-left va-mid">NAMA JAWATAN</th>
                        <td class="text-left va-mid">:</td>
                        <td class="text-left va-mid">
                            <?= ($row_aduan['jawatan'] != '' ? $row_aduan['jawatan'] : '-') ?>
                        </td>
                    </tr>

                    <tr>
                        <th class="text-left va-mid">NAMA JABATAN</th>
                        <td class="text-left va-mid">:</td>
                        <td class="text-left va-mid">
                            <?= ($row_aduan['unit'] != '' ? $row_aduan['unit'] : '-') ?>
                        </td>
                    </tr>

                    <tr>
                        <th class="text-left va-mid">NOMBOR TELEFON</th>
                        <td class="text-left va-mid">:</td>
                        <td class="text-left va-mid">
                            <?= ($row_aduan['nombor_telefon'] != '' ? $row_aduan['nombor_telefon'] : '-') ?>
                        </td>
                    </tr>

                    <tr>
                        <th class="text-left va-mid">LOKASI</th>
                        <td class="text-left va-mid">:</td>
                        <td class="text-left va-mid">
                            <?= ($row_aduan['kod_bangunan'] == 'LL' ? $row_aduan['lokasi_aduan'] : $row_aduan['nama_bangunan']) ?>
                        </td>
                    </tr>

                    <tr>
                        <th class="text-left va-mid">KEROSAKAN</th>
                        <td class="text-left va-mid">:</td>
                        <td class="text-left va-mid">
                            <?= ($row_aduan['kod_rosak'] == 'LL' ? $row_aduan['jenis_rosak'] : $row_aduan['nama_rosak']) ?>
                        </td>
                    </tr>

                    <tr>
                        <th class="text-left va-mid">KOMEN KEROSAKAN</th>
                        <td class="text-left va-mid">:</td>
                        <td class="text-left va-mid">
                            <?= ($row_aduan['komen'] != '' ? str_replace('<<>>', '<br>', $row_aduan['komen']) : '-') ?>
                        </td>
                    </tr>

                    <tr>
                        <th class="text-left va-mid">TARIKH ADUAN</th>
                        <td class="text-left va-mid">:</td>
                        <td class="text-left va-mid">
                            <?= ($row_aduan['tarikh_aduan'] != '' && $row_aduan['tarikh_aduan'] != '0000-00-00' ? getTarikhMasa('d/m/Y', $row_aduan['tarikh_aduan']) : '-') ?>
                        </td>
                    </tr>

                    <tr>
                        <th class="text-left va-mid">LAMPIRAN</th>
                        <td class="text-left va-mid">:</td>
                        <td class="text-left va-mid">
                            <?php 
                                $result_getlampiran = getLampiran($row_aduan['nombor_kes'], $row_aduan['nombor_pengadu']);
                                $total_getlampiran = mysql_num_rows($result_getlampiran);

                                if ($total_getlampiran > 0) {
                            ?>
                            <ul>
                                <?php while ($row_getlampiran = mysql_fetch_assoc($result_getlampiran)) { ?>
                                <li>
                                    <a href="<?= $row_getlampiran['path'] ?>" target="_blank"><?= $row_getlampiran['nama_fail'] ?></a>
                                </li>
                                <?php } ?>
                            </ul>
                            <ul id="senarai_fail"></ul>
                            <?php } else { ?>
                                -
                            <?php } ?>
                            
                        </td>
                    </tr>

                    <tr>
                        <th class="text-left va-mid">STATUS ADUAN <span class="text-danger">*</span></th>
                        <td class="text-left va-mid">:</td>
                        <td class="text-left va-mid">
                            <select class="form-control input-sm" name="status_baiki" id="status_baiki">
                                <?php foreach ($array_status as $k => $v) { ?>
                                <option <?= ($row_aduan['status_baiki'] == $k ? 'selected' : '') ?> value="<?= $k ?>"><?= $v ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th class="text-left va-mid">CATATAN <span class="text-danger">*</span></th>
                        <td class="text-left va-mid">:</td>
                        <td class="text-left va-mid">
                            <textarea class="form-control input-sm" name="catatan" id="catatan"><?= ($row_aduan['catatan'] != '' ? str_replace('<<>>', "\r\n", $row_aduan['catatan']) : '') ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th class="text-left va-mid">LAMPIRAN BAIKPULIH<span class="text-danger">*</span></th>
                        <td class="text-left va-mid">:</td>
                        <td class="text-left va-mid">
                        <input type="file" name="nama_fail" id="nama_fail">
                             </form>
                         <?php if (isset($_GET['remove']) && $_GET['remove'] != '') { ?>
                                    <?php } ?> 
                        </td>
                    </tr>
                         
                    <tr>
                        <td colspan="2" class="text-left va-mid"></td>
                        <td class="text-left va-mid">
                            <button class="btn btn-warning btn-sm" type="submit" name="btnUpdate">KEMASKINI</button>
                            <a href="<?= getUrlGet() ?>" class="btn btn-default btn-sm">RESET</a>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>
<?php endblock(); ?>