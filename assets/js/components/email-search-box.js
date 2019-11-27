import React from 'react';


export class EmailSearchBox extends React.Component {

    constructor(props) {
        super( props );
        this.state = {
            m: props.m,
            timer: null
        };

        if ( this.state.m ) {
            this.props.onSubmit( this.state.m );
        }
    }

    onChangeHandler( text ) {
        this.setState( {
            m: text,
        }, () => {
            if ( this.state.timer ) {
                clearTimeout( this.state.timer );
            }
            this.setState( {
                timer: setTimeout( () => {
                    this.submitHandler();
                }, 1500 ),
            } );
        } );
    }

    submitHandler() {
        if ( this.state.timer ) {
            clearTimeout( this.state.timer );
            this.setState( {
                timer: null
            } );
        }
        this.props.onSubmit( this.state.m );
    }



    render() {
        return (
            <form className='form' onSubmit={ e => { e.preventDefault(); this.submitHandler() } }>
                <div className="input-group mb-3">
                    <input type="search" className="form-control" placeholder="名前、メールアドレスで検索……" value={ this.state.m } onChange={ e => this.onChangeHandler( e.target.value ) } />
                    <div className="input-group-append">
                        <button className="btn btn-primary" type="submit">検索</button>
                    </div>
                </div>
            </form>
        );
    }

}
