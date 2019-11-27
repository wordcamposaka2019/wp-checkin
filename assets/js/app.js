/**
 * Search form result.
 */
import React from 'react';
import { SearchForm } from "./components/search-form";
import { EmailSearchForm } from "./components/email-search-form";
import { render } from 'react-dom';
import {Ticket} from "./components/ticket-page";

const form = document.getElementById( 'search-form' );
const params = new URLSearchParams(document.location.search);
const s = params.get('s');
const m = params.get('m');
if ( form ) {
  if (m) {
    render( <EmailSearchForm m={ m } />, form );
  } else {
    render( <SearchForm s={ s } />, form );
  }
}


const ticketWrapper = document.getElementById( 'ticket' );
if ( ticketWrapper ) {
  render( <Ticket id={ ticketWrapper.dataset.ticketId } />, ticketWrapper );
}
