<?php
/* -----------------------------------------------------
Nama   				: class_model.php
Pembuat 			: Stefanie Tanujaya
Tanggal Pembuatan 	: 6 Januari 2015
Version Control		:
v0.1 - 7 Januari 2015
	Menambahkan fungsi awal yaitu getAllClassByLecturer,
	getActiveTermYear,getClassInfoById,getClassTime, 
	getComboBoxAllYear, getDataTableByLecturer,
	countAll,countFiltered,updateAdditionalGrade,
	updateGradePercentage.
v0.2 - 18 Januari 2015
    - Memindahkan updateAdditionalGrade dan
    updateGradePercentage pada grade_model
----------------------------------------------------- */
/**
 * @property CI_DB_active_record $db
 * @property CI_DB_forge $dbforge
 * @property CI_Benchmark $benchmark
 * @property CI_Calendar $calendar
 * @property CI_Cart $cart
 * @property CI_Config $config
 * @property CI_Controller $controller
 * @property CI_Email $email
 * @property CI_Encrypt $encrypt
 * @property CI_Exceptions $exceptions
 * @property CI_Form_validation $form_validation
 * @property CI_Ftp $ftp
 * @property CI_Hooks $hooks
 * @property CI_Image_lib $image_lib
 * @property CI_Input $input
 * @property CI_Loader $load
 * @property CI_Log $log
 * @property CI_Model $model
 * @property CI_Output $output
 * @property CI_Pagination $pagination
 * @property CI_Parser $parser
 * @property CI_Profiler $profiler
 * @property CI_Router $router
 * @property CI_Session $session
 * @property CI_Sha1 $sha1
 * @property CI_Table $table
 * @property CI_Trackback $trackback
 * @property CI_Typography $typography
 * @property CI_Unit_test $unit_test
 * @property CI_Upload $upload
 * @property CI_URI $uri
 * @property CI_User_agent $user_agent
 * @property CI_Xmlrpc $xmlrpc
 * @property CI_Xmlrpcs $xmlrpcs
 * @property CI_Zip $zip
 *
 * Add additional libraries you wish
 * to use in your controllers here
 *
 * @property Class_model $class_model
 * @property Grade_model $grade_model
 *
 */
class Class_Model extends CI_Model {
	/* -----------------------------------------------------
	Function __construct()
	Mengeload Inisialisasi Awal Model class_model
	Input/Output : -
	----------------------------------------------------- */
	public function __construct(){
		parent::__construct();
		$this->load->database();
	}
	
	/* -----------------------------------------------------
	Function getAllClassByLecturer
	Mengambil data kelas yang diajar oleh lecturer tertentu 
	berdasarkan order_by, dan tahun_ajaran tertentu.
	Input: 
		[1] lecturer_id = (string) NIP dari Dosen yang dicari.
		[2] $orders = (assoc array) $nama_kolom => 'asc/desc'
		[3] $yearNow = (string) tahun ajaran yang ingin dioutputkan
	Output: Array Raw Kelas
	----------------------------------------------------- */
	public function getAllClassByLecturer($lecturer_id, $orders , $yearNow){				
		// Mengambil Data Kelas yang di ajar oleh lecturer
		$this->db->select('k.id as id, mk.id as kode_mk, mk.nama as nama_mk, k.hari as hari, k.jam_mulai as jam, r.nama as nama_ruang, k.status_konfirmasi as status_k, mk.jumlah_sks as sks, k.nama as nama_kelas, ik.jurusan as jurusan, k.tanggal_update as tanggal_update');
		$this->db->from('mata_kuliah mk, kelas k,informasi_kurikulum ik');
		$this->db->where('k.dosen_nip',$lecturer_id);
		$this->db->where('k.tahun_ajaran',$yearNow);
		$this->db->where('mk.id = k.mata_kuliah_id');
		$this->db->where('mk.informasi_kurikulum_id = ik.id');
		$this->db->join('ruangan r', 'r.id = k.ruangan_id','left');
		$this->db->where('k.status',1);
		foreach ($orders as $key => $value){
			$this->db->order_by($key, $value);
		}

		$results = $this->db->get()->result();
		return $results;
	}
	
	/* -----------------------------------------------------
	Function countAll()
	Menunjukkan banyaknya row yang ada tabel Kelas berdasarkan
	lecturer tertentu.
	Input: (string) lecturer_id = NIP Dosen
	Output :  (integer) banyak row pada tabel
	----------------------------------------------------- */
    public function countAll($lecturer_id) {
        $this->db->from('kelas');
		$this->db->where('dosen_nip',$lecturer_id);
        return $this->db->count_all_results();
    }
	/* -----------------------------------------------------
	Function countFiltered()
	Menunjukkan banyaknya row yang ada tabel Kelas berdasarkan
	lecturer tertentu dan year tertentu.
	Input:
		[1] lecturer_id = (string) NIP dari Dosen yang dicari.
		[2] $orders = (assoc array) $nama_kolom => 'asc/desc'
		[3] $yearNow = (string) tahun ajaran yang ingin dioutputkan
	Output :  (integer) banyak row pada tabel hasil
	----------------------------------------------------- */
	public function countFiltered($lecturer_id, $orders , $yearNow){
		if ($orders == null){
			$orders =['hari' => 'asc', 'jam_mulai' => 'asc'];
		}		
		if ($yearNow == null){
			// Mengambil Tahun Ajaran Sekarang
			$yearNow = $this->getActiveTermYear();
		}
		$this->getAllClassByLecturer($lecturer_id, $orders , $yearNow);
		return $this->db->affected_rows();
	}
	/* -----------------------------------------------------
	Function getClassTime
	Menghitung Jam Kelas berdasarkan jam_mulai dan sksnya.
	Input:
		[1] time = (string) dengan format ('H:i')
		[2] sks = (int) banyaknya sks  kelas tersebut
	Output :  (string) "[JamMulai] - [JamAkhir]"
	----------------------------------------------------- */
	public function getClassTime($time,$sks){
		if ($time == '-'){
			return "-";
		}
		//Mengambil Value Waktu SKS
		$this->db->select('value');
		$this->db->where('index','lama_sks');
		$lengthSKS = $this->db->get('data_umum')->row()->value;
		
		// Hitung Waktu
		$startSeconds =  strtotime($time);
		$endSeconds = $startSeconds + ($lengthSKS*$sks);
		return date('H:i',$startSeconds)." - ".date('H:i',$endSeconds);
	}
	
	/* -----------------------------------------------------
	Function getDataTableByLecturer
	Mendapatkan data kelas berdasarkan parameter berikut:
	Input:
		[1] lecturer_id = (string) NIP dari Dosen yang dicari.
		[2] $orders = (assoc array) $nama_kolom => 'asc/desc'
		[3] $yearNow = (string) tahun ajaran yang ingin dioutputkan
	Output : ARRAY[2] [0]array data kelas yang sudah diFilter dan Translate, [1] JUMLAH sks
	----------------------------------------------------- */
	public function getDataTableByLecturer($lecturer_login, $orders =null, $yearNow = null){
		$this->load->helper('url');
		if ($orders == null){
			$orders =['hari' => 'asc', 'jam_mulai' => 'asc'];
		}		
		if ($yearNow == null){
			// Mengambil Tahun Ajaran Sekarang
			$yearNow = $this->getActiveTermYear();
		}
		
		//Mengambil Data Berdasarkan Lecturer
		$results = $this->getAllClassByLecturer($lecturer_login, $orders, $yearNow);

		// Memproses data yang akan dikembalikan
		$classes = [];
		foreach ($results as $result){
			$class = $this->processClassData($result);
			$class[] = anchor('grade/view/'.$result->id,'Lihat Detail', 'class="btn btn-primary btn-sm"');
			$classes[] = $class;
		}
		return $classes;
	}
    public function getTotalSKSForLecturer($lecturer_login,$yearNow = null){
        $this->db->select_sum('mk.jumlah_sks');
        $this->db->where('k.dosen_nip',$lecturer_login);
        $this->db->where('mk.id = k.mata_kuliah_id');
        if ($yearNow != null){
            $this->db->where('k.tahun_ajaran',$yearNow);
        }
        $this->db->from('kelas k, mata_kuliah mk');
        return $this->db->get()->row()->jumlah_sks;
    }
	public function getClassInfoById($class_id, $lecturer_id){
		$this->db->select('k.id as id, mk.id as kode_mk, mk.nama as nama_mk, k.hari as hari, k.jam_mulai as jam, r.nama as nama_ruang, k.status_konfirmasi as status_k, mk.jumlah_sks as sks, k.nama as nama_kelas, d.nama as nama_dosen, mk.semester as semester, k.tahun_ajaran as tahun_ajaran, k.tambahan_grade as grade, k.persentase_uas as persen_uas, k.persentase_uts as persen_uts, k.persentase_tugas as persen_tugas, k.tanggal_update as tanggal_update, ik.jurusan, mk.lulus_minimal as lulus_minimal,k.komentar_kajur as komentar');
		$this->db->from('mata_kuliah mk, kelas k, dosen d,informasi_kurikulum ik');
		$this->db->where('mk.id = k.mata_kuliah_id');
		$this->db->where('d.nip = k.dosen_nip');
		$this->db->where('mk.informasi_kurikulum_id = ik.id');
		$this->db->where('k.status',1);
		$this->db->where('k.dosen_nip',$lecturer_id);
		$this->db->where('k.id',$class_id);
		$this->db->join('ruangan r', 'r.id = k.ruangan_id','left');
		$result = $this->db->get()->row();
		if ($this->db->affected_rows() > 0){
			$class = $this->processClassData($result);
			$class[] = $result->nama_dosen;
			$class[] = $result->sks;
			$class[] = $result->semester;
			$class[] = $result->status_k;
			$class[] = $result->tahun_ajaran;
			$class[] = $result->grade;
			$class[] = $result->persen_uts;
			$class[] = $result->persen_uas;
			$class[] = $result->persen_tugas;
			$class[] = $result->tanggal_update;
            $class[] = $result->id;
            $class[] = $result->lulus_minimal;
            $class[] = $result->komentar;
			return $class;
		}
		return false;
	}
	public function processClassData($result){
        $days = ["1" => "Senin", "2" => "Selasa", "3" =>  "Rabu", "4" => "Kamis", "5" => "Jumat", "6"=> "Sabtu", "7" => "Minggu"];
        $status = ["0" => '<span class="label label-default">Not Completed</span>',
            "1" => '<span class="label label-warning">Waiting</span>',
            "2" => '<span class="label label-danger">Need Revision</span>',
            "3" =>'<span class="label label-success">Completed</span>',];

        $class = [];
		
		$class[] =$result->nama_mk;
		$class[] = $result->jurusan;
        // Pengaturan SKS
        $class[] = $result->sks;
		$class[] = $result->nama_kelas;
		// Pengaturan Hari dan Jam
		if ($result->hari == NULL){
			$class[] = $result->hari;
		}
		else {
			$class[] = $days[$result->hari].', '. $this->getClassTime($result->jam, $result->sks);
		}
		// Pengaturan Ruang
		if ($result->nama_ruang != NULL){
			$class[] = $result->nama_ruang;
		}
		else {
			$class[] = "-";
		}
		$class[] = $status[$result->status_k]; // Pengaturan Status
		return $class;
	}
	public function getComboBoxAllYear(){
		$this->db->select('tahun_ajaran');
		$this->db->distinct();
		$this->db->from('kelas');
		$results = $this->db->get()->result();
		$arrComboBox = [];
		foreach ($results as $result){
			$key = str_replace('/','-',str_replace(' ','_',$result->tahun_ajaran));
			$arrComboBox[$key] = $result->tahun_ajaran;
		}
		return $arrComboBox;
	}
	public function getActiveTermYear(){
		$this->db->select('value');
		$this->db->where('index','tahun_ajaran_sekarang');
		return $this->db->get('data_umum')->row()->value;
	}
    public function getAllClassConnected($classId){
        $classes = [];
        $classes[] = $classId;
        $this->db->select('id');
        $this->db->where('kelas_id', $classId);
        $this->db->where('status','1');
        $this->db->from('kelas');
        $results = $this->db->get()->result();
        foreach ($results as $result){
            $classes[] = $result->id;
        }
        return $classes;
    }

	
	public function getClass($name)
	{
		$this->db->select('kelas.id, mata_kuliah.id as idmakul');
		$this->db->from('kelas, mata_kuliah');
		$this->db->where('kelas.status = 1');
		$this->db->where('kelas.mata_kuliah_id = mata_kuliah.id');
		$this->db->where('mata_kuliah.nama',$name);
		$this->db->order_by('kelas.id');
		$result = $this->db->get();
		return $result->result_array();
	}
	
	public function getStudent($classID, $courseID)
	{
		$this->db->from('kelas_mahasiswa');
		$this->db->where('kelas_id',$classID);
		$this->db->where('mata_kuliah_id',$courseID);
		$this->db->where('status_ambil','A');
		$this->db->or_where('status_ambil','a');
		return $this->db->count_all_results();
	}

    public function isClassExist($class_id){
        $this->db->where('id',$class_id);
        return $this->db->get('kelas')->num_rows();
    }
	public function getLecturerIdByClass($class_id){
        $this->db->select('dosen_nip');
        $this->db->where('id',$class_id);
        return $this->db->get('kelas')->row()->dosen_nip;
    }


}