/**
 * Search form result.
 */
import React from 'react';
import { SearchForm } from "./components/search-form";
import { render } from 'react-dom';
import {Ticket} from "./components/ticket-page";

const form = document.getElementById( 'search-form' );
const params = new URLSearchParams(document.location.search);
const s = params.get('s');
if ( form ) {
  render( <SearchForm s={ s } />, form );
}

const ticketWrapper = document.getElementById( 'ticket' );
if ( ticketWrapper ) {
  render( <Ticket id={ ticketWrapper.dataset.ticketId } />, ticketWrapper );
}
