/**
 * Search Form
 */
import React from 'react';
import { Component } from 'react';
import { EmailSearchBox } from "./email-search-box";
import { LoadingIndicator } from "./loading";
import {Ticket} from "./ticket-page";
import {fetchApi} from "./helper";

export class EmailSearchForm extends Component {

  constructor( props ) {
    super( props );
    this.state = {
      tickets: [],
      active: 0,
      loading: false,
      reload: false
    };
  }

  onSubmit( text ){
    if ( this.state.loading || ! text.length ) {
      // If loading, fix
      return;
    }
    this.setState( {
      text: text,
      loading: true,
      active: 0,
      reload: false,
      tickets: [],
    }, () => {
      fetchApi( '/search?m=' + encodeURIComponent( text ) )
        .then( res => res.json() )
        .then( items => {
          console.log( items );
          this.setState( {
            tickets: items,
          } );
        } )
        .catch( res => {
          console.log( res );
        } )
        .finally( res => {
          this.setState( {
            loading: false,
          } );
        } );
    } );
  }

  render(){
    if (this.state.reload === true) {
      this.onSubmit(this.state.text);
    }
    return (
      <div className='search'>
        <EmailSearchBox m={ this.props.m } onSubmit={ text => this.onSubmit( text ) } />

        <hr />

        <LoadingIndicator loading={ this.state.loading } />

        <p className='text-center text-muted'>{ this.state.tickets.length }件が見つかりました。</p>

        { this.state.tickets.length ? (
          <table className='table search-result'>
            <thead>
              <tr>
                <th>#</th>
                <th>名前</th>
                <th>種別</th>
                <th>役</th>
                <th>メール</th>
                <th>チェックイン</th>
                <th>アクション</th>
              </tr>
            </thead>
            <tbody>
              { this.state.tickets.map( ( ticket, index ) => {
                return (
                  <tr ref={ ticket.id }>
                    <th>{ticket.id}</th>
                    <td>{ticket.familyname} {ticket.givenname}</td>
                    <td>{ticket.category}</td>
                    <td>{ticket.role}</td>
                    <td>{ticket.email}</td>
                    <td>{ticket.checkedin ? (<i className="fas fa-check"></i>) : null}</td>
                    <td>
                      <button className='btn btn-primary' onClick={ e => this.setState( { active: ticket.id} ) }>表示</button>
                    </td>
                  </tr>
                );
              } ) }
            </tbody>
          </table>
        ) : (
          <div className='alert alert-danger text-center'>
            該当するチケットはありません。
          </div>
        ) }

        { this.state.active ? (
          <div className='backdrop'>
            <div className='backdrop-inner'>
              <button className='btn btn-link backdrop-close' onClick={ e => this.setState( { active: 0, reload: true } ) }>閉じる</button>
              <h3 className='text-center'>チケット詳細</h3>
              <div className='ticket-wrapper'>
                <Ticket id={ this.state.active } />
              </div>
            </div>
          </div>
        ) : null }

      </div>
    );
  }

}
