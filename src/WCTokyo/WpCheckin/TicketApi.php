<?php

namespace WCTokyo\WpCheckin;


use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\DocumentSnapshot;
use Hametuha\SingletonPattern\Singleton;
use Slim\Http\Request;
use Slim\Http\Response;

class TicketApi extends Singleton {

	/**
	 * Search ticket.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return Response
	 */
	public function handle_search( Request $request, Response $response, array $args ) {
		try {
			$query = $request->getQueryParam( 's' );
			if ( ! $query ) {
				throw new \Exception( '検索キーワードが指定されていません。', 404 );
			}
			$query = explode( ' ', str_replace( '　', ' ', $query ) );
			$result = $this->search( $query );
			return $response->withJson( $result );
		} catch ( \Exception $e ) {
			return $response->withJson( [], 404 );
		}
	}
	
	public function handle_qr( Request $request, Response $response, array $args ) {
		try {
//			$queries = [];
//			foreach ( [ 'f', 'g', 'e' ] as $key ) {
//				if ( $param = $request->getQueryParam( $key ) ) {
//					$queries[] = $param;
//				}
//			}
//			if ( ! $queries ) {
//				throw new \Exception( 'No queries set.' );
//			}
//			$result = $this->search( $queries );
//			if ( 1 !== count( $result) ) {
//				throw new \Exception( 'Not found.' );
//			}
//			list( $data ) = $result;
            $param = $request->getQueryParam('s');
			$url = sprintf( 'https://wco2019.unplat.info/?s=%s', $param );
		} catch ( \Exception $e ) {
			$url = 'https://wco2019.unplat.info';
		} finally {
			$src = str_replace( '&amp;', '&', $this->generate_qr( $url ) );
			$content = file_get_contents( $src );
			header( 'Content-Type: image/png' );
			echo $content;
			exit;
		}
	}
	
	/**
	 * Generate image url of qr code.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function generate_qr( $text ) {
		$url = 'https://chart.apis.google.com/chart?';
		$queries = [];
		foreach ( [
			'cht' => 'qr',
			'chs' => '300x300',
			'chl' => $text,
		] as $key => $val ) {
			$queries[] = sprintf( '%s=%s', $key, rawurlencode( $val ) );
		}
		$url .= implode( '&amp;', $queries );
		return $url;
	}
	
	/**
	 * Search tickets.
	 *
	 * @param string[] $query
	 *
	 * @return array[]
	 */
	private function search( $query ) {
		$result = [];
		$tickets = FireBase::get_instance()
						   ->db()
						   ->collection( 'Tickets' )
						   ->documents();
		foreach ( $tickets as $ticket ) {
			/** @var DocumentSnapshot $ticket */
			if ( ! $ticket->exists() ) {
				continue;
			}
			$data  = $this->convert_to_array( $ticket );
			$string = implode( '', $data );
			foreach ( $query as $q ) {
				if ( false === strpos( $string, $q ) ) {
					continue 2;
				}
			}
			$result[] = $data;
		}
		return $result;
	}
	
	/**
	 * Returns JSON.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return Response
	 */
	public function handle_get( Request $request, Response $response, array $args ) {
		$document = $this->get_document( $args['ticket_id'] );
		if ( $document ) {
			$document = $this->add_items( $document );
			return $response->withJson( $document );
		} else {
			return $response->withJson( null, 404 );
		}
	}
	
	/**
	 * Handle post request.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return Response
	 */
	public function handle_post( Request $request, Response $response, array $args ) {
		try {
			$document = $this->get_reference( $args[ 'ticket_id' ] );
			if ( ! $document->snapshot()->exists() ) {
				throw new \Exception( '該当するチケットが存在しません。', 404 );
			}
			$document->update( [
				[
					'path' => 'checkedin',
					'value' => date( 'Y-m-d H:i:s' ),
				],
			] );
			return $response->withJson( $this->add_items( $this->convert_to_array( $document->snapshot() ) ) );
		} catch ( \Exception $e ) {
			return $response->withJson( [
				'message' => $e->getMessage(),
			], $e->getCode() );
		}
	}
	
	/**
	 * Uncheck document.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return Response
	 */
	public function handle_delete( Request $request, Response $response, array $args ) {
		try {
			$document = $this->get_reference( $args[ 'ticket_id' ] );
			if ( ! $document->snapshot()->exists() ) {
				throw new \Exception( '該当するチケットが存在しません。', 404 );
			}
			$document->update( [
				[
					'path' => 'checkedin',
					'value' => '',
				],
			] );
			return $response->withJson( $this->convert_to_array( $document->snapshot() ) );
		} catch ( \Exception $e ) {
			return $response->withJson( [
				'message' => $e->getMessage(),
			], $e->getCode() );
		}
	}
	
	/**
	 * Get document snapshot.
	 *
	 * @param string $ticket_id
	 *
	 * @return DocumentReference
	 */
	protected function get_reference( $ticket_id ) {
		return FireBase::get_instance()
							->db()
							->collection( 'Tickets' )
							->document( $ticket_id );
	}
	
	/**
	 * Get document.
	 *
	 * @param string $ticket_id
	 * @return array
	 */
	protected function get_document( $ticket_id ) {
		$document = $this->get_reference( $ticket_id )->snapshot();
		if ( $document->exists() ) {
			return $this->convert_to_array( $document );
		} else {
			return [];
		}
	}
	
	/**
	 * Convert user data to array.
	 *
	 * @param DocumentSnapshot $document
	 *
	 * @return array
	 */
	public function convert_to_array( $document ) {
		$data = $document->data();
		$data['id'] = $document->id();
		// Add role.
		$role = '一般参加';
		foreach ( [
                      'WCOSAKA2019-CONTRIBUTE' => 'コントリビューター',
                      'WCOSAKA2019-STAFF' => 'スタッフ',
                      'WCOSAKA2019-SPEAKER' => 'スピーカー',
                      'WCOSAKA2019-STUDENT' => '学生',
                      'wct-sponsor-2019' => 'スポンサー',
		] as $coupon => $label ) {
			if ( isset( $data['coupon'] ) && false !== strpos( $data['coupon'], $coupon ) ) {
				$role = $label;
				break;
			}
		}
		if ( false !== strpos( $data['category'], 'マイクロスポンサー' ) ) {
			 $role = 'マイクロスポンサー';
		}
		foreach ([
                     'WCOSAKA2019-SAR44Z' => 'JetPack',
                     'WCOSAKA2019-SOR44Z' => 'JetPack',
                     'WCOSAKA2019-SAQCJI' => 'WooCommerce',
                     'WCOSAKA2019-SOQCJI' => 'WooCommerce',
                     'WCOSAKA2019-SA6R1H' => 'bluehost',
                     'WCOSAKA2019-SO6R1H' => 'bluehost',
                     'WCOSAKA2019-SA6Y5E' => 'GoDaddy',
                     'WCOSAKA2019-SO6Y5E' => 'GoDaddy',
                     'WCOSAKA2019-SAH07T' => 'HubSpot',
                     'WCOSAKA2019-SOH07T' => 'HubSpot',
                     'WCOSAKA2019-SA1D1E' => 'PayPal',
                     'WCOSAKA2019-SO1D1E' => 'PayPal',
                     'WCOSAKA2019-SAPBP9' => 'Sakura Internet',
                     'WCOSAKA2019-SOPBP9' => 'Sakura Internet',
                     'WCOSAKA2019-SOLNM7' => 'WelCart',
                     'WCOSAKA2019-SOS1MT' => 'SITEGUARD',
                     'WCOSAKA2019-SOPM5G' => 'HAMWORKS',
                     'WCOSAKA2019-SOQ7LL' => 'LIQUID PRESS',
                     'WCOSAKA2019-SO5GNI' => 'cookbiz',
                     'WCOSAKA2019-SA7B55' => 'GMOペパボ',
                     'WCOSAKA2019-SO7B55' => 'GMOペパボ',
                     'WCOSAKA2019-SALKNH' => 'GMOペパボ',
                     'WCOSAKA2019-SOLKNH' => 'GMOペパボ',
                     'WCOSAKA2019-SAM23L' => 'エックスサーバー',
                     'WCOSAKA2019-SOM23L' => 'エックスサーバー',
                     'WCOSAKA2019-SO3D3X' => 'プライムストラテジー',
                     'WCOSAKA2019-SA0YD6' => 'モリサワ',
                     'WCOSAKA2019-SO0YD6' => 'モリサワ',
                     'WCOSAKA2019-SA6WHH' => 'Pantheon',
                     'WCOSAKA2019-SO6WHH' => 'Pantheon',
                     'WCOSAKA2019-SAB5K2' => 'アズポケット',
                     'WCOSAKA2019-SOB5K2' => 'アズポケット',
                     'WCOSAKA2019-SAPQ3G' => 'GMOクラウド',
                     'WCOSAKA2019-SOPQ3G' => 'GMOクラウド',
                     'WCOSAKA2019-SANS20' => 'Weglot',
                     'WCOSAKA2019-SONS20' => 'Weglot',
                     'WCOSAKA2019-SA6G14' => '職人工房',
                     'WCOSAKA2019-SO6G14' => '職人工房',
                     'WCOSAKA2019-SA43QT' => 'カゴヤジャパン',
                     'WCOSAKA2019-SO43QT' => 'カゴヤジャパン',
                     'WCOSAKA2019-SAPP7F' => 'SBテクノロジー',
                     'WCOSAKA2019-SOPP7F' => 'SBテクノロジー',
                     'WCOSAKA2019-SAL4L0' => 'モンキーレンチ',
                     'WCOSAKA2019-SOL4L0' => 'モンキーレンチ',
                     'WCOSAKA2019-SOR21T' => 'クリーク・アンド・リバー',
                     'WCOSAKA2019-SAO15B' => 'SiteGround',
                     'WCOSAKA2019-SO1TQA' => 'Kinsta',
                 ] as $coupon => $label) {
            if ( isset( $data['coupon'] ) && false !== strpos( $data['coupon'], $coupon ) ) {
                $role = 'スポンサー: '  .$label;
                break;
            }
        }
		$data['role'] = $role;
		$sorted       = [
			'familyname' => $data['familyname'],
			'givenname'  => $data['givenname'],
		];
		foreach ( $data as $key => $val ) {
			if ( in_array( $key, [ 'familyname', 'givenname' ] ) ) {
				continue;
			}
			$sorted[ $key ] = $val;
		}
		return $sorted;
	}
	
	/**
	 * Convert array
	 *
	 * @param array $document
	 *
	 * @return array
	 */
	public function add_items( $document ) {
		$document['items'] = [
			'パンフレット',
			'ストラップ',
			'シール' . ( $document['u20'] ? '（成人）' : '（未成年）' ),
		];
		if ( false !== strpos( $document['role'], 'マイクロスポンサー' ) ) {
			$tshirt = 'パーカー（マイクロスポンサー）';
			if ( ! empty( $document['tshirtsize'] ) ) {
				$tshirt .= ' - ' . $document['tshirtsize'];
			} else {
				$tshirt .= ' - 要サイズ確認';
			}
			$document['items'][] = $tshirt;
		}
		if ( false !== strpos( $document['role'], 'スピーカー' ) ) {
			$document['items'][] = 'パーカー（スピーカー）';
		}
		
		return $document;
	}
}
